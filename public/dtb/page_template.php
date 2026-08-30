<?php
/**
 * Template for the published, shared playlist page.
 *
 * dtb_publish() includes this inside an output buffer with $playlist in scope
 * and DTB_ROOT defined. The result is written to <token>/<slug>/index.html,
 * always exactly two directories below the app root -- hence ../../media/...
 *
 * Everything is inlined. The only external references are the audio files, and
 * they are relative, so the whole directory can be moved to any subdirectory or
 * domain and keep working.
 *
 * Locals are prefixed dtbt_ on purpose: this file is included straight into
 * dtb_publish()'s scope, which reads $pageDir *after* the include.
 */

$dtbt_name   = (string) ($playlist['name'] ?? 'Playlist');
$dtbt_token  = (string) ($playlist['token'] ?? '');
$dtbt_note   = trim((string) ($playlist['note'] ?? ''));
$dtbt_tracks = array_values(array_filter((array) ($playlist['tracks'] ?? []), 'is_array'));
$dtbt_count  = count($dtbt_tracks);
$dtbt_total  = dtb_total_duration(['tracks' => $dtbt_tracks]);

// Media lives at <root>/media/<token>/<file>; this page lives at
// <root>/<token>/<slug>/index.html, so two levels up gets back to the root.
$dtbt_media_base = '../../media/' . rawurlencode($dtbt_token) . '/';

$dtbt_rows = [];
foreach ($dtbt_tracks as $dtbt_track) {
    $dtbt_file = basename((string) ($dtbt_track['file'] ?? ''));
    $dtbt_rows[] = [
        'id'       => (string) ($dtbt_track['id'] ?? ''),
        'title'    => (string) ($dtbt_track['title'] ?? '') !== '' ? (string) $dtbt_track['title'] : 'Untitled Track',
        'src'      => $dtbt_media_base . rawurlencode($dtbt_file),
        'duration' => (float) ($dtbt_track['duration'] ?? 0),
        'type'     => (string) ($dtbt_track['type'] ?? 'audio/mpeg'),
    ];
}

$dtbt_json = json_encode(
    ['token' => $dtbt_token, 'name' => $dtbt_name, 'tracks' => $dtbt_rows],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);

$dtbt_css = @file_get_contents(DTB_ROOT . '/player.css');
$dtbt_js  = @file_get_contents(DTB_ROOT . '/player.js');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Unlisted links: keep them out of search engines entirely. -->
<meta name="robots" content="noindex, nofollow, noarchive">
<title><?= h($dtbt_name) ?></title>
<style>
<?= $dtbt_css ?>
</style>
</head>
<body<?= $dtbt_count ? ' class="dtb-has-bar"' : '' ?>>

<div class="dtb-wrap">
    <header class="dtb-header">
        <h1 class="dtb-title"><?= h($dtbt_name) ?></h1>
        <p class="dtb-meta">
            <?= $dtbt_count === 1 ? '1 track' : $dtbt_count . ' tracks' ?><span id="dtb-total"<?= $dtbt_total > 0 ? '' : ' hidden' ?>><?= $dtbt_total > 0 ? ' &middot; ' . h(dtb_format_time($dtbt_total)) : '' ?></span>
        </p>
<?php if ($dtbt_note !== ''): ?>
        <p class="dtb-note"><?= h($dtbt_note) ?></p>
<?php endif; ?>
        <div class="dtb-controls dtb-jsonly">
            <button type="button" class="dtb-btn" id="dtb-share">Share</button>
<?php if ($dtbt_count): ?>
            <button type="button" class="dtb-btn" id="dtb-autoplay" aria-pressed="true">Autoplay on</button>
            <button type="button" class="dtb-btn" id="dtb-repeat" aria-pressed="false">Repeat off</button>
<?php endif; ?>
        </div>
    </header>

    <main>
<?php if (!$dtbt_count): ?>
        <div class="dtb-empty">
            <strong>No tracks yet</strong>
            Nothing has been added to this playlist. Check back soon.
        </div>
<?php else: ?>
        <ol class="dtb-tracks" id="dtb-tracks">
<?php foreach ($dtbt_rows as $dtbt_i => $dtbt_row): ?>
            <li class="dtb-track" data-index="<?= (int) $dtbt_i ?>" data-id="<?= h($dtbt_row['id']) ?>">
                <a class="dtb-row" href="<?= h($dtbt_row['src']) ?>" type="<?= h($dtbt_row['type']) ?>">
                    <span class="dtb-num"><?= (int) $dtbt_i + 1 ?></span>
                    <span class="dtb-name"><?= h($dtbt_row['title']) ?></span>
                    <span class="dtb-dur"><?= h(dtb_format_time($dtbt_row['duration'])) ?></span>
                </a>
            </li>
<?php endforeach; ?>
        </ol>
        <p class="dtb-hint dtb-jsonly">Space plays or pauses &middot; arrow keys seek &middot; N and P skip tracks.</p>
<?php endif; ?>
    </main>
</div>

<?php if ($dtbt_count): ?>
<div class="dtb-bar dtb-jsonly" id="dtb-bar">
    <div class="dtb-bar-inner">
        <div class="dtb-now">
            <button type="button" class="dtb-skip" id="dtb-prev" aria-label="Previous track" title="Previous track">&#9664;&#9664;</button>
            <span class="dtb-now-title" id="dtb-now-title" aria-live="polite"><?= h($dtbt_rows[0]['title']) ?></span>
            <button type="button" class="dtb-skip" id="dtb-next" aria-label="Next track" title="Next track">&#9654;&#9654;</button>
        </div>
        <!--
          ONE audio element for the entire page, never replaced or re-created:
          iOS/Safari unblock the *element* on the first user gesture, so a freshly
          created element inside an `ended` handler cannot autoplay and next-track
          would silently die on phones. player.js only swaps its src.
        -->
        <audio class="dtb-audio" id="dtb-audio" controls preload="metadata" playsinline></audio>
    </div>
</div>
<?php endif; ?>

<script type="application/json" id="dtb-data"><?= $dtbt_json ?></script>
<script>
<?= $dtbt_js ?>
</script>
</body>
</html>
