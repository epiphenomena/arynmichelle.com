<?php
/**
 * Shared chrome for the demo-tape-board admin pages.
 *
 * Expects (all optional):
 *   $title      -- <title> text
 *   $show_save  -- true to render the Cancel/Save pair wired to #main-form
 *   $cancel_url -- where Cancel goes (defaults to the dashboard)
 *   $toast      -- ['text' => string, 'error' => bool] message to flash on load
 */
if (!function_exists('dtb_url')) {
    defined('DTB_IN_ADMIN') || define('DTB_IN_ADMIN', true);
    require_once __DIR__ . '/../lib.php';
}
$title      = isset($title) ? $title : 'Playlists';
$show_save  = !empty($show_save);
$cancel_url = isset($cancel_url) ? $cancel_url : dtb_admin_url();
$toast      = isset($toast) && is_array($toast) ? $toast : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo h($title); ?></title>
    <style>
        <?php echo file_get_contents(__DIR__ . '/admin.css'); ?>
    </style>
</head>
<body>

    <div id="toast" class="<?php echo $toast ? 'show' . (!empty($toast['error']) ? ' toast-error' : '') : ''; ?>"><?php
        echo $toast ? h($toast['text']) : '';
    ?></div>

    <header class="sticky-header">
        <nav>
            <span class="brand">Demo Tape Board</span>
            <a href="<?php echo h(dtb_admin_url()); ?>">Playlists</a>
        </nav>
        <?php if ($show_save): ?>
        <div class="header-actions">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo h($cancel_url); ?>'">Cancel</button>
            <button type="submit" form="main-form" id="save-btn" class="btn btn-primary" disabled>Save</button>
        </div>
        <?php endif; ?>
    </header>
