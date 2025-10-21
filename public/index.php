<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aryn Michelle</title>
    <style>
        <?php echo file_get_contents('./home.css'); ?>
    </style>
</head>
<body>
    <?php include_once('socialicons.php'); ?>

    <div class="bg-image-container">
        <div class="overlay"></div>

        <div class="center-box">
            <div class="center-box-content">
                <h1 class="center-title">ARYN MICHELLE</h1>
                <div class="social-links">
                    <?php render_spotify_icon('#'); ?>
                    <?php render_youtube_icon('#'); ?>
                    <?php render_youtube_music_icon('#'); ?>
                    <?php render_x_icon('#'); ?>
                    <?php render_instagram_icon('#'); ?>
                    <?php render_tiktok_icon('#'); ?>
                    <?php render_facebook_icon('#'); ?>
                    <?php render_apple_music_icon('#'); ?>
                </div>
            </div>
        </div>

        <main class="main-grid">
            <!-- Quadrant 1: Music -->
            <a href="template.html" class="quadrant-link">
                <div class="quadrant-content">
                    <h2 class="quadrant-title">MUSIC</h2>
                    <p class="quadrant-subtitle">albums, EPs, streaming, charts</p>
                </div>
            </a>

            <!-- Quadrant 2: Features -->
            <a href="template.html" class="quadrant-link">
                <div class="quadrant-content">
                    <h2 class="quadrant-title">FEATURES</h2>
                    <p class="quadrant-subtitle">videos, interviews, podcasts</p>
                </div>
            </a>

            <!-- Quadrant 3: About/Contact -->
            <a href="template.html" class="quadrant-link">
                <div class="quadrant-content">
                    <h2 class="quadrant-title">ABOUT/CONTACT</h2>
                    <p class="quadrant-subtitle">info, bio, booking</p>
                </div>
            </a>

            <!-- Quadrant 4: Resources -->
            <a href="template.html" class="quadrant-link">
                <div class="quadrant-content">
                    <h2 class="quadrant-title">RESOURCES</h2>
                    <p class="quadrant-subtitle">playlists, songwriting</p>
                </div>
            </a>
        </main>

    </div>

</body>
</html>

