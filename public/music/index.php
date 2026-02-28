<?php
include '../page_header.php';
include '../socialicons.php';

// Read content from JSON file
$music_file = '../data/music.json';
$music_data = json_decode(file_get_contents($music_file), true);

$latest = $music_data['latest_album'];
$previous = $music_data['previous_projects'];
?>

<main>
    <!-- Latest Album Hero Section -->
    <section class="latest-album-hero">
        <div class="hero-content">
            <div class="hero-image">
                <img src="<?php echo htmlspecialchars($latest['cover_image']); ?>" alt="<?php echo htmlspecialchars($latest['title']); ?> Cover">
            </div>
            <div class="hero-text">
                <span class="label">Latest Release</span>
                <h1><?php echo htmlspecialchars($latest['title']); ?></h1>
                <p><?php echo htmlspecialchars($latest['description']); ?></p>
                
                <div class="hero-links">
                    <?php if (!empty($latest['spotify_url'])) render_spotify_icon($latest['spotify_url']); ?>
                    <?php if (!empty($latest['apple_music_url'])) render_apple_music_icon($latest['apple_music_url']); ?>
                    <?php if (!empty($latest['youtube_url'])) render_youtube_icon($latest['youtube_url']); ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Previous Projects Grid -->
    <section class="previous-projects">
        <h2>Discography</h2>
        <div class="projects-grid">
            <?php foreach ($previous as $project): ?>
                <a href="<?php echo htmlspecialchars($project['link']); ?>" class="project-card" target="_blank">
                    <div class="project-image">
                        <img src="<?php echo htmlspecialchars($project['cover_image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?> Cover">
                        <div class="project-overlay">
                            <span>Listen</span>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include '../page_footer.php'; ?>
