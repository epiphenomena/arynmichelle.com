<?php
include '../page_header.php';

// Read content from JSON file
$features_file = '../data/features.json';
$features_data = file_exists($features_file) ? json_decode(file_get_contents($features_file), true) : [];
?>

<main>
    <section class="previous-projects">
        <h1>Features & Interviews</h1>
        <div class="projects-grid">
            <?php foreach ($features_data as $feature): ?>
                <a href="<?php echo htmlspecialchars($feature['link']); ?>" class="project-card" target="_blank">
                    <div class="project-image">
                        <img src="<?php echo htmlspecialchars($feature['image']); ?>" alt="<?php echo htmlspecialchars($feature['title']); ?> Cover">
                        <div class="project-overlay">
                            <span>view</span>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include '../page_footer.php'; ?>
