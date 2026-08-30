<?php
/**
 * Playlist editor: upload audio, rename, reorder, retitle and remove tracks.
 *
 * Two independent forms post back here (POST -> redirect -> GET):
 *   action=upload  multipart, appends tracks
 *   action=save    id order + titles + name/note, deletes anything left out
 *
 * The token always travels in the URL, never in the POST body: an oversized
 * upload empties $_POST entirely, and we still need to know where to send the
 * browser back to. admin/.htaccess rewrites /admin/<token>/ to this script with
 * the token in ?t=, so it still arrives as $_GET['t'].
 */
define('DTB_IN_ADMIN', true);
require_once __DIR__ . '/../lib.php';
dtb_bootstrap();

/* --------------------------------------------------------------- plumbing */

$token = isset($_GET['t']) ? (string) $_GET['t'] : '';

function dtb_edit_redirect($token, array $params)
{
    $url = dtb_edit_url($token);
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url, true, 303);
    exit;
}

function dtb_bail_to_dashboard()
{
    header('Location: ' . dtb_admin_url() . '?err=notfound', true, 303);
    exit;
}

/* An oversized request empties $_POST and $_FILES, so check before reading them,
   otherwise a too-large upload looks exactly like a save that did nothing. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && dtb_post_overflow()) {
    if (!dtb_valid_token($token)) {
        dtb_bail_to_dashboard();
    }
    dtb_edit_redirect($token, ['err' => 'overflow']);
}

if (!dtb_valid_token($token)) {
    dtb_bail_to_dashboard();
}

$playlist = dtb_load($token);
if (!$playlist) {
    dtb_bail_to_dashboard();
}

/* ---------------------------------------------------------------- actions */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    /* -- A. upload ---------------------------------------------------- */
    if ($action === 'upload') {
        $errors = [];
        $added = 0;

        // Durations are measured in the browser, keyed by "name|size".
        $durations = [];
        if (isset($_POST['durations'])) {
            $decoded = json_decode((string) $_POST['durations'], true);
            if (is_array($decoded)) {
                $durations = $decoded;
            }
        }

        $files = isset($_FILES['files']) ? $_FILES['files'] : null;
        if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
            // PHP transposes a multi-file field into parallel arrays.
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $code = (int) $files['error'][$i];
                if ($code === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $name = (string) $files['name'][$i];
                if ($code !== UPLOAD_ERR_OK) {
                    $errors[] = ($name !== '' ? $name . ': ' : '') . dtb_upload_error_message($code);
                    continue;
                }
                $size = (int) $files['size'][$i];
                $key = $name . '|' . $size;
                $duration = isset($durations[$key]) && is_numeric($durations[$key]) && is_finite((float) $durations[$key])
                    ? max(0, (float) $durations[$key])
                    : 0;

                $result = dtb_store_upload($token, [
                    'name'     => $name,
                    'tmp_name' => $files['tmp_name'][$i],
                    'size'     => $size,
                    'error'    => $code,
                ], $duration);

                if (isset($result['track'])) {
                    $playlist['tracks'][] = $result['track'];
                    $added++;
                } else {
                    // Errors are plain text everywhere; they get escaped on output.
                    $errors[] = (string) $result['error'];
                }
            }
        }

        if ($added > 0 && !dtb_save($playlist)) {
            $errors[] = 'The playlist could not be written to disk.';
        }

        $params = [];
        if ($added > 0) {
            $params['msg'] = 'uploaded';
            $params['n'] = $added;
        }
        if ($errors) {
            $params['errs'] = substr(implode("\n", array_slice($errors, 0, 5)), 0, 800);
        }
        if (!$params) {
            $params['err'] = 'nofiles';
        }
        dtb_edit_redirect($token, $params);
    }

    /* -- B. details + tracks ------------------------------------------ */
    if ($action === 'save') {
        $name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
        if ($name !== '') {
            $playlist['name'] = $name;
        }
        $playlist['note'] = isset($_POST['note']) ? trim((string) $_POST['note']) : '';

        // The track editor is JS-driven, and JS stamps this field on submit. Without
        // it the track list never really reached us (JS failure or a browser quirk),
        // and an empty ids[] must not be read as "delete every track". Gating on the
        // marker rather than on ids[] is what lets "remove them all" -- which
        // legitimately submits no ids at all -- still go through.
        if (($_POST['tracks_submitted'] ?? '') !== '1') {
            dtb_save($playlist);
            dtb_edit_redirect($token, ['msg' => 'savedmeta']);
        }

        $byId = [];
        foreach ($playlist['tracks'] as $track) {
            $id = (string) (isset($track['id']) ? $track['id'] : '');
            if ($id !== '') {
                $byId[$id] = $track;
            }
        }
        $titles = (isset($_POST['titles']) && is_array($_POST['titles'])) ? $_POST['titles'] : [];

        $ordered = [];
        $kept = [];
        $submittedIds = (isset($_POST['ids']) && is_array($_POST['ids'])) ? $_POST['ids'] : [];
        foreach ($submittedIds as $rawId) {
            $id = (string) $rawId;
            if (!isset($byId[$id]) || isset($kept[$id])) {
                continue; // unknown or duplicated id
            }
            $kept[$id] = true;
            $track = $byId[$id];
            $title = '';
            if (isset($titles[$id])) {
                $raw = (string) $titles[$id];
                $collapsed = preg_replace('/\s+/u', ' ', $raw);   // null on invalid UTF-8
                $title = trim($collapsed === null ? $raw : $collapsed);
            }
            if ($title !== '') {
                $track['title'] = $title;
            }
            $ordered[] = $track;
        }

        // Only once the new list is built do the leftovers get their files removed.
        $removed = 0;
        foreach ($byId as $id => $track) {
            if (!isset($kept[$id])) {
                dtb_delete_track_file($token, $track);
                $removed++;
            }
        }

        $playlist['tracks'] = $ordered;
        $ok = dtb_save($playlist);
        dtb_edit_redirect($token, $ok
            ? ['msg' => 'saved', 'removed' => $removed]
            : ['err' => 'writefail']);
    }

    dtb_edit_redirect($token, []);
}

/* --------------------------------------------------------------- messages */

$toast = null;
$banner_errors = [];
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$err = isset($_GET['err']) ? $_GET['err'] : '';

if ($msg === 'created') {
    $toast = ['text' => 'Playlist created. Add some tracks below.'];
} elseif ($msg === 'uploaded') {
    $n = max(0, (int) (isset($_GET['n']) ? $_GET['n'] : 0));
    $toast = ['text' => 'Added ' . $n . ' ' . ($n === 1 ? 'track' : 'tracks') . '.'];
} elseif ($msg === 'saved') {
    $removed = max(0, (int) (isset($_GET['removed']) ? $_GET['removed'] : 0));
    $toast = ['text' => 'Playlist saved.' . ($removed > 0
        ? ' Removed ' . $removed . ' ' . ($removed === 1 ? 'track' : 'tracks') . '.'
        : '')];
} elseif ($msg === 'savedmeta') {
    $toast = ['text' => 'Saved the name and note. The track list was not submitted, so it is unchanged.'];
}

if ($err === 'overflow') {
    $banner_errors[] = 'That upload was larger than the ' . dtb_format_bytes(dtb_upload_limit())
        . ' limit, so the browser sent nothing the server could keep. Try fewer files at a time.';
} elseif ($err === 'nofiles') {
    $banner_errors[] = 'No files were chosen, so nothing was uploaded.';
} elseif ($err === 'writefail') {
    $banner_errors[] = 'The playlist could not be written to disk. Check that the data folder is writable.';
}
if (isset($_GET['errs']) && $_GET['errs'] !== '') {
    foreach (explode("\n", (string) $_GET['errs']) as $line) {
        if (trim($line) !== '') {
            $banner_errors[] = $line;
        }
    }
}
if ($banner_errors && !$toast) {
    $toast = ['text' => count($banner_errors) === 1 ? $banner_errors[0] : 'Some files could not be uploaded.', 'error' => true];
}

/* ------------------------------------------------------------------ render */

$share = dtb_share_url($playlist);
$limit = dtb_format_bytes(dtb_upload_limit());
$self = dtb_edit_url($token);
$trackCount = count($playlist['tracks']);

$title = $playlist['name'] . ' -- Demo Tape Board';
$show_save = true;
$cancel_url = dtb_admin_url();
include __DIR__ . '/header.php';
?>

<main>
    <div class="container">
        <h1><?php echo h($playlist['name']); ?></h1>
        <p class="lede">
            <?php echo $trackCount; ?> <?php echo $trackCount === 1 ? 'track' : 'tracks'; ?>
            &middot; <?php echo h(dtb_format_time(dtb_total_duration($playlist))); ?> total
        </p>

        <?php if ($banner_errors): ?>
        <div class="banner banner-error">
            <strong>Some things did not work:</strong>
            <ul>
                <?php foreach ($banner_errors as $line): ?>
                <li><?php echo h($line); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="panel">
            <h2>Share link</h2>
            <div class="share-row">
                <input type="text" value="<?php echo h($share); ?>" readonly
                       aria-label="Share link" onfocus="this.select();">
                <button type="button" class="btn btn-secondary btn-small" data-copy="<?php echo h($share); ?>">Copy link</button>
            </div>
            <span class="hint">Anyone with this link can listen. It is the only protection on the playlist.</span>
        </div>

        <!-- A. Upload ------------------------------------------------------ -->
        <div class="panel">
            <h2>Add tracks</h2>
            <form method="post" action="<?php echo h($self); ?>" enctype="multipart/form-data" id="upload-form">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="durations" id="durations" value="">
                <div class="form-group">
                    <label for="files">Audio files</label>
                    <input type="file" id="files" name="files[]" multiple
                           accept="audio/*,.mp3,.m4a,.aac,.ogg,.opus,.wav,.flac">
                    <span class="hint">
                        mp3, m4a, aac, ogg, opus, wav or flac. The server accepts up to
                        <?php echo h($limit); ?> per request &mdash; upload in batches if you have more.
                    </span>
                    <div class="file-summary" id="file-summary"></div>
                </div>
                <button type="submit" class="btn btn-primary" id="upload-btn">Upload</button>
            </form>
        </div>

        <!-- B. Details and tracks ------------------------------------------ -->
        <form method="post" action="<?php echo h($self); ?>" id="main-form">
            <input type="hidden" name="action" value="save">

            <div class="panel">
                <h2>Details</h2>
                <div class="form-group">
                    <label for="name">Playlist name</label>
                    <input type="text" id="name" name="name" maxlength="120"
                           value="<?php echo h($playlist['name']); ?>" required>
                    <span class="hint-warn">
                        Renaming changes the share link &mdash; the playlist name is part of the URL.
                        The old link stops working as soon as you save, so send the new one to anyone
                        who still needs it.
                    </span>
                </div>
                <div class="form-group">
                    <label for="note">Note for the band (optional)</label>
                    <textarea id="note" name="note" rows="3"
                              placeholder="Rough mixes, ignore the drums..."><?php echo h(isset($playlist['note']) ? $playlist['note'] : ''); ?></textarea>
                </div>
            </div>

            <div class="panel">
                <h2>Tracks</h2>
                <?php if (!$playlist['tracks']): ?>
                    <p class="meta">No tracks yet. Upload some audio above.</p>
                <?php else: ?>
                    <p class="hint" style="margin-bottom:1rem;">
                        Use the arrows to reorder (or drag the number on a desktop). Removals are
                        marked here and only take effect when you press Save.
                    </p>
                    <!-- Stamped to "1" by JS on submit. Its presence proves the track
                         rows really were submitted, so removing every track (which sends
                         no ids[] at all) stays distinguishable from a JS failure. Must
                         stay empty in the markup: a static value would always be sent
                         and the server-side guard could never fire. -->
                    <input type="hidden" name="tracks_submitted" id="tracks-submitted" value="">
                    <div class="track-list" id="track-list">
                        <?php foreach ($playlist['tracks'] as $i => $track):
                            $id = (string) $track['id'];
                        ?>
                        <div class="track-row" data-id="<?php echo h($id); ?>">
                            <div class="track-index" draggable="true" title="Drag to reorder"><?php echo $i + 1; ?></div>
                            <div class="track-main">
                                <input type="hidden" name="ids[]" value="<?php echo h($id); ?>">
                                <input type="text" name="titles[<?php echo h($id); ?>]"
                                       value="<?php echo h($track['title']); ?>"
                                       aria-label="Track title" maxlength="200">
                                <span class="removal-note">Removed when you save</span>
                            </div>
                            <div class="track-facts">
                                <?php echo h(dtb_format_time(isset($track['duration']) ? $track['duration'] : 0)); ?>
                                &middot; <?php echo h(dtb_format_bytes((int) (isset($track['size']) ? $track['size'] : 0))); ?>
                            </div>
                            <div class="track-buttons">
                                <button type="button" class="btn btn-secondary" data-move="up" aria-label="Move up" title="Move up">&uarr;</button>
                                <button type="button" class="btn btn-secondary" data-move="down" aria-label="Move down" title="Move down">&darr;</button>
                                <button type="button" class="btn btn-danger" data-remove>Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</main>

<script>
(function () {
    'use strict';

    var saveBtn = document.getElementById('save-btn');
    function markDirty() { if (saveBtn) { saveBtn.disabled = false; } }

    /* ---------------------------------------------------- track list */
    var list = document.getElementById('track-list');

    function renumber() {
        if (!list) { return; }
        var rows = list.querySelectorAll('.track-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].querySelector('.track-index').textContent = String(i + 1);
        }
    }

    if (list) {
        list.addEventListener('click', function (event) {
            var btn = event.target.closest('button');
            if (!btn || !list.contains(btn)) { return; }
            var row = btn.closest('.track-row');
            if (!row) { return; }

            if (btn.hasAttribute('data-move')) {
                if (btn.getAttribute('data-move') === 'up') {
                    var prev = row.previousElementSibling;
                    if (prev && prev.classList.contains('track-row')) { list.insertBefore(row, prev); }
                } else {
                    var next = row.nextElementSibling;
                    if (next && next.classList.contains('track-row')) { list.insertBefore(next, row); }
                }
                renumber();
                markDirty();
                btn.focus();
                return;
            }

            if (btn.hasAttribute('data-remove')) {
                // Nothing is deleted now: the row is flagged and its id is left out
                // of the submission, so Save is what actually removes the track.
                var pending = row.classList.toggle('pending-remove');
                var hidden = row.querySelector('input[name="ids[]"]');
                if (hidden) { hidden.disabled = pending; }
                btn.textContent = pending ? 'Undo' : 'Remove';
                btn.classList.toggle('btn-danger', !pending);
                btn.classList.toggle('btn-secondary', pending);
                markDirty();
            }
        });

        /* Drag and drop, from the number handle only, so text selection in the
           title inputs keeps working. Touch users get the arrow buttons. */
        var dragged = null;

        list.addEventListener('dragstart', function (event) {
            var handle = event.target.closest('.track-index');
            if (!handle) { return; }
            dragged = handle.closest('.track-row');
            if (!dragged) { return; }
            dragged.classList.add('dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                try { event.dataTransfer.setData('text/plain', dragged.dataset.id || ''); } catch (e) {}
            }
        });

        list.addEventListener('dragover', function (event) {
            if (!dragged) { return; }
            event.preventDefault();
            if (event.dataTransfer) { event.dataTransfer.dropEffect = 'move'; }
            var over = event.target.closest('.track-row');
            if (!over || over === dragged || !list.contains(over)) { return; }
            var box = over.getBoundingClientRect();
            var after = event.clientY > box.top + box.height / 2;
            list.insertBefore(dragged, after ? over.nextElementSibling : over);
        });

        list.addEventListener('drop', function (event) {
            if (dragged) { event.preventDefault(); }
        });

        list.addEventListener('dragend', function () {
            if (!dragged) { return; }
            dragged.classList.remove('dragging');
            dragged = null;
            renumber();
            markDirty();
        });
    }

    /* ------------------------------------------------- upload durations */
    var fileInput = document.getElementById('files');
    var durationsInput = document.getElementById('durations');
    var uploadBtn = document.getElementById('upload-btn');
    var summary = document.getElementById('file-summary');
    var uploadForm = document.getElementById('upload-form');
    var durations = {};

    function probe(file) {
        // Resolve either way within 5s: a file the browser cannot decode must not
        // leave the Upload button disabled forever.
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(file);
            var audio = new Audio();
            var settled = false;
            var timer = null;

            function done(value) {
                if (settled) { return; }
                settled = true;
                if (timer) { clearTimeout(timer); }
                URL.revokeObjectURL(url);
                audio.src = '';
                if (typeof value === 'number' && isFinite(value) && value > 0) {
                    durations[file.name + '|' + file.size] = Math.round(value * 100) / 100;
                }
                resolve();
            }

            timer = setTimeout(function () { done(null); }, 5000);
            audio.preload = 'metadata';
            audio.addEventListener('loadedmetadata', function () { done(audio.duration); });
            audio.addEventListener('error', function () { done(null); });
            audio.src = url;
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var files = Array.prototype.slice.call(fileInput.files || []);
            durations = {};                        // a new selection replaces the old
            if (durationsInput) { durationsInput.value = '{}'; }

            if (summary) {
                if (!files.length) {
                    summary.textContent = '';
                } else {
                    summary.textContent = '';
                    var head = document.createElement('strong');
                    head.textContent = files.length + ' file' + (files.length === 1 ? '' : 's') + ' selected';
                    var state = document.createElement('span');
                    state.id = 'probe-state';
                    state.textContent = ' (reading lengths...)';
                    var ul = document.createElement('ul');
                    files.forEach(function (f) {
                        var li = document.createElement('li');
                        li.textContent = f.name;            // text node: filenames are never parsed as HTML
                        ul.appendChild(li);
                    });
                    summary.appendChild(head);
                    summary.appendChild(state);
                    summary.appendChild(ul);
                }
            }
            if (!files.length) { return; }

            if (uploadBtn) { uploadBtn.disabled = true; }

            var guard = new Promise(function (resolve) { setTimeout(resolve, 6000); });
            Promise.race([Promise.all(files.map(probe)), guard]).then(function () {
                if (durationsInput) { durationsInput.value = JSON.stringify(durations); }
                if (uploadBtn) { uploadBtn.disabled = false; }
                var ready = document.getElementById('probe-state');
                if (ready) { ready.textContent = ' (ready to upload)'; }
            });
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', function () {
            if (durationsInput) { durationsInput.value = JSON.stringify(durations); }
        });
    }

    // Stamp the details form so the server knows the track rows genuinely came from
    // this page. Removing every track submits no ids[] at all, and without this
    // marker the server could not tell that apart from JS having failed -- so it
    // would refuse the save rather than risk wiping the list.
    var detailsForm = document.getElementById('main-form');
    var submitFlag = document.getElementById('tracks-submitted');
    if (detailsForm && submitFlag) {
        detailsForm.addEventListener('submit', function () {
            submitFlag.value = '1';
        });
    }
}());
</script>

<?php include __DIR__ . '/footer.php'; ?>
