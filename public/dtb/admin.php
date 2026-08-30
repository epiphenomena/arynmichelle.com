<?php
/**
 * Dashboard: every playlist, its share link, and the create/delete/rebuild actions.
 *
 * All writes are POST -> redirect -> GET so a refresh never repeats an action.
 */
require_once __DIR__ . '/lib.php';
dtb_bootstrap();

$self = dtb_url('admin.php');

/* ------------------------------------------------------------------ actions */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // An oversized POST arrives with $_POST and $_FILES both empty, which would
    // otherwise look like a no-op. Catch it before reading any field.
    if (dtb_post_overflow()) {
        header('Location: ' . $self . '?err=overflow', true, 303);
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create') {
        $playlist = dtb_create(isset($_POST['name']) ? $_POST['name'] : '');
        if ($playlist) {
            // Straight into the editor so she can start uploading immediately.
            header('Location: ' . dtb_url('edit.php') . '?t=' . urlencode($playlist['token']) . '&msg=created', true, 303);
        } else {
            header('Location: ' . $self . '?err=create', true, 303);
        }
        exit;
    }

    if ($action === 'delete') {
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        if (!dtb_valid_token($token)) {
            header('Location: ' . $self . '?err=notfound', true, 303);
            exit;
        }
        dtb_delete($token);
        header('Location: ' . $self . '?msg=deleted', true, 303);
        exit;
    }

    if ($action === 'rebuild') {
        $count = dtb_publish_all();
        header('Location: ' . $self . '?msg=rebuilt&n=' . $count, true, 303);
        exit;
    }

    header('Location: ' . $self, true, 303);
    exit;
}

/* ----------------------------------------------------------------- messages */

$toast = null;
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$err = isset($_GET['err']) ? $_GET['err'] : '';

if ($msg === 'deleted') {
    $toast = ['text' => 'Playlist deleted, along with its audio and its page.'];
} elseif ($msg === 'rebuilt') {
    $n = max(0, (int) (isset($_GET['n']) ? $_GET['n'] : 0));
    $toast = ['text' => 'Rebuilt ' . $n . ' playlist ' . ($n === 1 ? 'page' : 'pages') . '.'];
} elseif ($msg === 'saved') {
    $toast = ['text' => 'Playlist saved.'];
}

if ($err === 'notfound') {
    $toast = ['text' => 'That playlist no longer exists.', 'error' => true];
} elseif ($err === 'create') {
    $toast = ['text' => 'The playlist could not be created. Check that the data folder is writable.', 'error' => true];
} elseif ($err === 'overflow') {
    $toast = ['text' => 'That request was larger than the ' . dtb_format_bytes(dtb_upload_limit()) . ' limit and was dropped.', 'error' => true];
}

$playlists = dtb_all();

$title = 'Playlists';
$show_save = false;
include __DIR__ . '/admin_header.php';
?>

<main>
    <div class="container">
        <h1>Playlists</h1>
        <p class="lede">
            Each playlist has its own unguessable link. That link is the only protection:
            anyone who has it can listen, and anyone who does not cannot find the page.
            Deleting a playlist permanently removes its audio files and its page.
        </p>

        <div class="panel">
            <h2>New playlist</h2>
            <form method="post" action="<?php echo h($self); ?>" class="inline-form">
                <input type="hidden" name="action" value="create">
                <input type="text" id="new-name" name="name" placeholder="e.g. April demos"
                       aria-label="Playlist name" maxlength="120" required>
                <button type="submit" class="btn btn-primary">Create &amp; add tracks</button>
            </form>
        </div>

        <?php if (!$playlists): ?>
            <div class="empty-state">
                <p><strong>No playlists yet.</strong></p>
                <p>Create one above, then upload mp3s and send the link to the band.</p>
            </div>
        <?php else: ?>
            <div class="playlist-grid">
                <?php foreach ($playlists as $p):
                    $count = count($p['tracks']);
                    $share = dtb_share_url($p);
                    $editUrl = dtb_url('edit.php') . '?t=' . urlencode($p['token']);
                ?>
                <div class="playlist-card">
                    <h3><?php echo h($p['name']); ?></h3>
                    <p class="meta">
                        <?php echo $count; ?> <?php echo $count === 1 ? 'track' : 'tracks'; ?>
                        &middot; <?php echo h(dtb_format_time(dtb_total_duration($p))); ?>
                        &middot; updated <?php echo h(date('M j, Y', (int) ($p['updated'] ?? time()))); ?>
                    </p>

                    <div class="share-row">
                        <input type="text" value="<?php echo h($share); ?>" readonly
                               aria-label="Share link for <?php echo h($p['name']); ?>"
                               onfocus="this.select();">
                        <button type="button" class="btn btn-secondary btn-small" data-copy="<?php echo h($share); ?>">Copy link</button>
                    </div>

                    <div class="card-actions">
                        <a class="btn btn-primary btn-small" href="<?php echo h($editUrl); ?>">Manage tracks</a>
                        <a class="btn btn-secondary btn-small" href="<?php echo h($share); ?>" target="_blank" rel="noopener">Open</a>
                        <form method="post" action="<?php echo h($self); ?>"
                              onsubmit="return confirm('Delete &quot;<?php echo h(addslashes($p['name'])); ?>&quot; and all of its audio? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="token" value="<?php echo h($p['token']); ?>">
                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="panel" style="margin-top:2rem;">
            <h2>Maintenance</h2>
            <form method="post" action="<?php echo h($self); ?>" class="inline-form">
                <input type="hidden" name="action" value="rebuild">
                <button type="submit" class="btn btn-secondary btn-small">Rebuild all pages</button>
                <span class="hint" style="flex:1 1 220px;">Use after a site deploy if a link 404s. It regenerates every playlist page from its saved data; nothing is lost.</span>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
