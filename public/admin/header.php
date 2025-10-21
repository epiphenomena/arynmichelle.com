<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Admin Dashboard'; ?></title>
    <style>
        <?php
        $css_content = file_get_contents(__DIR__ . '/admin.css');
        echo $css_content;
        ?>
    </style>
</head>
<body>

    <!-- Optional toast notification -->
    <!-- Add the 'show' class to make it visible, e.g., for a success message -->
    <div id="toast" class="">
        Your changes have been saved successfully!
    </div>

    <header class="sticky-header">
        <nav>
            <a href="/admin/">Admin Index</a>
            <a href="/" target="_blank">View Site</a>
        </nav>
        <div class="header-actions">
            <button type="button" form="main-form" onclick="window.location.href='/admin/'" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="main-form" id="save-btn" class="btn btn-primary" disabled>Save</button>
        </div>
    </header>