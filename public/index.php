<!DOCTYPE html>
<html lang="en">
<?php
// Load settings from JSON file
$settings_file = __DIR__ . '/data/settings.json';
$settings = json_decode(file_get_contents($settings_file), true);

// Load home page data
$home_file = __DIR__ . '/data/home.json';
$home_data = json_decode(file_get_contents($home_file), true);

// Set default values if settings are not available
$site_title = $settings['site-title'] ?? 'Aryn Michelle';
$twitter_image = $settings['social-media-card'] ?? '/media/social-card-image.jpg';

// Load and modify CSS to include the dynamic background
$css_content = file_get_contents('./home.css');
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <style>
        <?php echo $css_content; ?>
    </style>
    <meta property="og:image" content="<?php echo htmlspecialchars($twitter_image); ?>">
</head>
<body>
    <?php
    // Load home page quadrant data
    $home_file = __DIR__ . '/data/home.json';
    $home_data = json_decode(file_get_contents($home_file), true);
    $quadrants = $home_data['quadrants'];

    // Load social media links data
    $social_file = __DIR__ . '/data/social.json';
    $social_data = json_decode(file_get_contents($social_file), true);
    $social_links = $social_data['social_links'];

    include_once('socialicons.php');
    ?>

    <div class="bg-image-container">
        <div class="overlay"></div>

        <div class="center-box">
            <div class="center-box-content">
                <h1 class="center-title">ARYN MICHELLE</h1>
                <div class="social-links">
                    <?php foreach ($social_links as $link): ?>
                        <?php
                        $url = $link['url'] ?? '#';
                        $type = $link['type'] ?? 'spotify';

                        // Call the appropriate function based on the type
                        switch ($type) {
                            case 'spotify':
                                render_spotify_icon($url);
                                break;
                            case 'youtube':
                                render_youtube_icon($url);
                                break;
                            case 'youtube_music':
                                render_youtube_music_icon($url);
                                break;
                            case 'x':
                                render_x_icon($url);
                                break;
                            case 'instagram':
                                render_instagram_icon($url);
                                break;
                            case 'tiktok':
                                render_tiktok_icon($url);
                                break;
                            case 'facebook':
                                render_facebook_icon($url);
                                break;
                            case 'apple_music':
                                render_apple_music_icon($url);
                                break;
                            default:
                                render_spotify_icon($url); // default to spotify
                                break;
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <main class="main-grid">
            <?php foreach ($quadrants as $index => $quadrant): ?>
            <?php
            $title = $quadrant['title'] ?? 'Quadrant ' . ($index + 1);
            $subtitle = $quadrant['subtitle'] ?? 'Subtitle';
            $link = !empty($quadrant['link']) ? $quadrant['link'] : 'about/';
            ?>
            <a href="<?php echo htmlspecialchars($link); ?>" class="quadrant-link">
                <div class="quadrant-content">
                    <h2 class="quadrant-title"><?php echo htmlspecialchars($title); ?></h2>
                    <p class="quadrant-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </main>

    </div>

</body>
</html>

