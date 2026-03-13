<?php 
// Load social media links data
$social_file = __DIR__ . '/data/social.json';
$social_data = json_decode(file_get_contents($social_file), true);
$social_links = $social_data['social_links'];

// Include social icons functions
include_once('socialicons.php');
?>

<footer class="site-footer">
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
</footer>

<script>
    // Fade in images once they've loaded
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', function() {
                    img.classList.add('loaded');
                });
                img.addEventListener('error', function() {
                    img.classList.add('loaded');
                });
            }
        });
    });
</script>

</body>
</html>
