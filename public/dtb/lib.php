<?php
/**
 * Shared helpers for the demo-tape-board playlist manager.
 *
 * Source of truth:  data/playlists/<token>.json
 * Uploaded audio:   media/<token>/<random>.<ext>
 * Published page:   <token>/<playlist-slug>/index.html   (self-contained static HTML)
 *
 * Public entry points sit in this directory and the admin lives in admin/, which
 * declares DTB_IN_ADMIN so dtb_base_url() can strip the extra level. The base URL
 * is always derived from SCRIPT_NAME, so the app is subdirectory-agnostic.
 */

define('DTB_ROOT', __DIR__);
define('DTB_DATA', DTB_ROOT . '/data');
define('DTB_LISTS', DTB_DATA . '/playlists');
define('DTB_MEDIA', DTB_ROOT . '/media');

// Extensions we accept on upload, and the type we hand back to <audio>.
const DTB_AUDIO_TYPES = [
    'mp3'  => 'audio/mpeg',
    'm4a'  => 'audio/mp4',
    'aac'  => 'audio/aac',
    'ogg'  => 'audio/ogg',
    'oga'  => 'audio/ogg',
    'opus' => 'audio/ogg',
    'wav'  => 'audio/wav',
    'flac' => 'audio/flac',
];

/* ------------------------------------------------------------------ setup */

/**
 * rsync deploys never create data/ or media/ (the Rakefile excludes them), so
 * the app creates them itself, along with the guards that would live inside.
 */
function dtb_bootstrap()
{
    foreach ([DTB_DATA, DTB_LISTS, DTB_MEDIA] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    $guards = [
        DTB_DATA . '/.htaccess'  => "Options -Indexes\n\nRequire all denied\n",
        DTB_MEDIA . '/.htaccess' => "Options -Indexes\n",
    ];
    foreach ($guards as $path => $body) {
        if (!file_exists($path)) {
            @file_put_contents($path, $body);
        }
    }
}

/* ------------------------------------------------------------- small utils */

/**
 * Web path of the app root (the directory holding this file).
 *
 * Admin scripts live one level down in admin/ and declare DTB_IN_ADMIN before
 * including this file, so the extra level can be stripped. Deriving it from
 * SCRIPT_NAME keeps the app working in any subdirectory without configuration,
 * and stays correct under the admin/ rewrite because SCRIPT_NAME still names
 * the real script.
 */
function dtb_base_url()
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (defined('DTB_IN_ADMIN')) {
        $base = dirname($base);
    }
    return ($base === '/' || $base === '.' || $base === '\\') ? '' : rtrim($base, '/');
}

function dtb_url($path = '')
{
    return dtb_base_url() . '/' . ltrim($path, '/');
}

/** Web path inside the admin. Everything the admin serves lives under admin/,
 *  which is where the HTTP auth is anchored -- see the note in admin/.htaccess. */
function dtb_admin_url($path = '')
{
    return dtb_base_url() . '/admin/' . ltrim($path, '/');
}

/** Clean editor URL for one playlist: <base>/admin/<token>/ */
function dtb_edit_url($token)
{
    return dtb_admin_url($token . '/');
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function dtb_new_token()
{
    return bin2hex(random_bytes(16)); // 32 hex characters, 128 bits
}

/** Tokens index into the filesystem, so nothing that is not 32 hex chars gets through. */
function dtb_valid_token($token)
{
    return is_string($token) && preg_match('/^[a-f0-9]{32}$/', $token) === 1;
}

function dtb_slug($name)
{
    $slug = strtolower(trim((string) $name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug === '' ? 'playlist' : substr($slug, 0, 60);
}

function dtb_format_time($seconds)
{
    $seconds = (int) round((float) $seconds);
    if ($seconds <= 0) {
        return '--:--';
    }
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    return $h > 0
        ? sprintf('%d:%02d:%02d', $h, $m, $s)
        : sprintf('%d:%02d', $m, $s);
}

/** Write via a temp file in the same directory so a reader never sees a half-written file. */
function dtb_write_atomic($path, $contents)
{
    $tmp = $path . '.tmp' . getmypid();
    if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
        return false;
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    @chmod($path, 0644);
    return true;
}

/** Recursive delete, hard-limited to paths inside this app. */
function dtb_rrmdir($dir)
{
    $real = realpath($dir);
    $root = realpath(DTB_ROOT);
    if ($real === false || $root === false || strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }
    foreach (scandir($real) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $real . '/' . $entry;
        is_dir($path) && !is_link($path) ? dtb_rrmdir($path) : @unlink($path);
    }
    @rmdir($real);
}

/* ------------------------------------------------------------ playlist I/O */

function dtb_list_path($token)
{
    return DTB_LISTS . '/' . $token . '.json';
}

function dtb_load($token)
{
    if (!dtb_valid_token($token)) {
        return null;
    }
    $path = dtb_list_path($token);
    if (!is_file($path)) {
        return null;
    }
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data) || !dtb_valid_token($data['token'] ?? '')) {
        return null;
    }
    $data['tracks'] = array_values(array_filter($data['tracks'] ?? [], 'is_array'));
    return $data;
}

/** Persist a playlist and republish its static page. */
function dtb_save($playlist)
{
    dtb_bootstrap();
    $playlist['slug'] = dtb_slug($playlist['name']);
    $playlist['updated'] = time();
    $json = json_encode($playlist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!dtb_write_atomic(dtb_list_path($playlist['token']), $json)) {
        return false;
    }
    // The record is committed either way; a false return here means "saved, but the
    // shared page could not be written", which is worth telling her about rather
    // than reporting success behind a link that 404s.
    return dtb_publish($playlist);
}

function dtb_all()
{
    dtb_bootstrap();
    $playlists = [];
    foreach (glob(DTB_LISTS . '/*.json') ?: [] as $file) {
        $playlist = dtb_load(basename($file, '.json'));
        if ($playlist) {
            $playlists[] = $playlist;
        }
    }
    usort($playlists, function ($a, $b) {
        return ($b['updated'] ?? 0) <=> ($a['updated'] ?? 0);
    });
    return $playlists;
}

function dtb_create($name)
{
    $token = dtb_new_token();
    $playlist = [
        'token'    => $token,
        'name'     => trim($name) !== '' ? trim($name) : 'Untitled Playlist',
        'slug'     => '',
        'note'     => '',
        'created'  => time(),
        'updated'  => time(),
        'tracks'   => [],
    ];
    return dtb_save($playlist) ? $playlist : null;
}

function dtb_delete($token)
{
    if (!dtb_valid_token($token)) {
        return;
    }
    dtb_rrmdir(DTB_MEDIA . '/' . $token);   // uploaded audio
    dtb_rrmdir(DTB_ROOT . '/' . $token);    // published page
    @unlink(dtb_list_path($token));
}

function dtb_total_duration($playlist)
{
    $total = 0;
    foreach (($playlist['tracks'] ?? []) as $track) {
        $total += (float) ($track['duration'] ?? 0);
    }
    return $total;
}

/* --------------------------------------------------------- static publishing */

/** Path of the shared page, relative to the app base: "<token>/<slug>/". */
function dtb_share_path($playlist)
{
    return $playlist['token'] . '/' . dtb_slug($playlist['name']) . '/';
}

function dtb_share_url($playlist)
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . dtb_url(dtb_share_path($playlist));
}

/**
 * Render the playlist to a single self-contained HTML file. Assets are inlined so
 * the page has no dependency but its own audio files, which it reaches with a
 * relative path -- it keeps working wherever the directory is hosted.
 */
function dtb_publish($playlist)
{
    $token = $playlist['token'];
    if (!dtb_valid_token($token)) {
        return false;
    }
    $slug = dtb_slug($playlist['name']);
    $tokenDir = DTB_ROOT . '/' . $token;

    if (!is_dir($tokenDir) && !@mkdir($tokenDir, 0755, true)) {
        return false;
    }
    // A rename leaves the old slug directory behind; the token holds one playlist.
    foreach (glob($tokenDir . '/*', GLOB_ONLYDIR) ?: [] as $existing) {
        if (basename($existing) !== $slug) {
            dtb_rrmdir($existing);
        }
    }
    $pageDir = $tokenDir . '/' . $slug;
    if (!is_dir($pageDir) && !@mkdir($pageDir, 0755, true)) {
        return false;
    }

    $html = dtb_render_page($playlist);
    if (!is_string($html) || $html === '') {
        return false; // never overwrite a working page with nothing
    }
    return dtb_write_atomic($pageDir . '/index.html', $html);
}

/**
 * Render the page in its own scope. Kept separate so a local in the template can
 * never collide with a variable dtb_publish() still needs after the include.
 */
function dtb_render_page($playlist)
{
    ob_start();
    include DTB_ROOT . '/page_template.php';
    return ob_get_clean();
}

/** Regenerate every page, e.g. after a deploy replaced the templates. */
function dtb_publish_all()
{
    $count = 0;
    foreach (dtb_all() as $playlist) {
        if (dtb_publish($playlist)) {
            $count++;
        }
    }
    return $count;
}

/* -------------------------------------------------------------- uploading */

/** True when the request exceeded post_max_size, which silently empties $_POST. */
function dtb_post_overflow()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function dtb_upload_limit()
{
    $toBytes = function ($value) {
        $value = trim((string) $value);
        $unit = strtolower(substr($value, -1));
        $num = (float) $value;
        if ($unit === 'g') return $num * 1073741824;
        if ($unit === 'm') return $num * 1048576;
        if ($unit === 'k') return $num * 1024;
        return $num;
    };
    return min($toBytes(ini_get('upload_max_filesize')), $toBytes(ini_get('post_max_size')));
}

function dtb_format_bytes($bytes)
{
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}

function dtb_upload_error_message($code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'the file is larger than the ' . dtb_format_bytes(dtb_upload_limit()) . ' upload limit';
        case UPLOAD_ERR_PARTIAL:
            return 'the upload was interrupted';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return 'the server could not write the file';
        default:
            return 'the upload failed';
    }
}

/**
 * Move one uploaded file into media/<token>/ under a random name and return the
 * track record. Random names keep the audio as unguessable as the playlist link.
 */
function dtb_store_upload($token, $file, $duration)
{
    $original = (string) $file['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(DTB_AUDIO_TYPES[$ext])) {
        return ['error' => $original . ' was skipped: ' . ($ext !== '' ? '.' . $ext : 'that file type') . ' is not an audio format'];
    }
    $dir = DTB_MEDIA . '/' . $token;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['error' => 'Could not create the upload folder.'];
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
        return ['error' => $original . ' could not be saved.'];
    }
    @chmod($dir . '/' . $stored, 0644);

    $title = pathinfo($original, PATHINFO_FILENAME);
    $title = trim(preg_replace('/[_]+/', ' ', $title));

    return ['track' => [
        'id'       => bin2hex(random_bytes(8)),
        'title'    => $title !== '' ? $title : 'Untitled Track',
        'file'     => $stored,
        'type'     => DTB_AUDIO_TYPES[$ext],
        'size'     => (int) $file['size'],
        'duration' => round((float) $duration, 2),
        'added'    => time(),
    ]];
}

function dtb_delete_track_file($token, $track)
{
    $file = basename((string) ($track['file'] ?? ''));
    if ($file !== '' && dtb_valid_token($token)) {
        @unlink(DTB_MEDIA . '/' . $token . '/' . $file);
    }
}
