<?php
include '../page_header.php';

// Include Parsedown for converting markdown to HTML
require_once '../Parsedown.php';

// Read content from markdown file
$content_file = '../data/features.md';
$content = file_exists($content_file) ? file_get_contents($content_file) : '# Features Page

Default content.';

// Convert markdown to HTML
$parsedown = new Parsedown();
$parsed_content = $parsedown->text($content);
?>

<main>
    <?php echo $parsed_content; ?>
</main>

<?php include '../page_footer.php'; ?>
