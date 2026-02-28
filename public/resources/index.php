<?php
include '../page_header.php';

// Read content from JSON file
$resources_file = '../data/resources.json';
$resources_data = file_exists($resources_file) ? json_decode(file_get_contents($resources_file), true) : [];
?>

<main>
    <div class="resources-container">
        <?php foreach ($resources_data as $index => $section): ?>
            <section class="resource-section">
                <div class="resource-image">
                    <img src="<?php echo htmlspecialchars($section['image']); ?>" alt="<?php echo htmlspecialchars($section['title']); ?>">
                </div>
                <div class="resource-info">
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                    <ul class="resource-links">
                        <?php foreach ($section['links'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['text']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<?php include '../page_footer.php'; ?>
