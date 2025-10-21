<?php
// Load settings from JSON file
$settings_file = __DIR__ . '/data/settings.json';
$settings = json_decode(file_get_contents($settings_file), true);

// Set default values if settings are not available
$site_title = $settings['site-title'] ?? 'Aryn Michelle';
$twitter_image = $settings['social-media-card'] ?? '/media/social-card-image.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <style>
        <?php echo file_get_contents(__DIR__ . '/pages.css'); ?>
    </style>
    <meta property="og:image" content="<?php echo htmlspecialchars($twitter_image); ?>">
</head>
<body>

    <div class="container">

        <header class="site-header">
            <a href="/">Aryn Michelle</a>
        </header>