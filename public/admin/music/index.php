<?php
$title = "Music Page - Admin";

// Define the music content file path
$music_file = __DIR__ . '/../../data/music.md';

// Load music content from markdown file
$music_content = file_exists($music_file) ? file_get_contents($music_file) : "# Music Page

Your music page content goes here.";

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $music_content = $_POST['music_content'] ?? '';
    
    // Ensure data directory exists
    $data_dir = __DIR__ . '/../../data/';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // Save music content to markdown file
    file_put_contents($music_file, $music_content);
    
    // Refresh content after saving
    $music_content = file_get_contents($music_file);
}

// Include Parsedown for preview
require_once __DIR__ . '/../../Parsedown.php';
$parsedown = new Parsedown();
$preview_content = $parsedown->text($music_content);

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Music Page Content</h1>
        <p>Edit the content for the music page using Markdown syntax.</p>

        <form method="post" id="main-form">
            <div class="form-group">
                <label for="music_content">Content (Markdown)</label>
                <textarea id="music_content" name="music_content" rows="20" class="markdown-editor"><?php echo htmlspecialchars($music_content); ?></textarea>
                <small class="form-help">Use Markdown syntax for formatting. <a href="https://www.markdownguide.org/cheat-sheet/" target="_blank">Markdown Cheat Sheet</a></small>
            </div>
        </form>
        
        <div class="form-group" style="margin-top: 2rem;">
            <h2>Preview</h2>
            <div class="markdown-preview">
                <?php echo $preview_content; ?>
            </div>
        </div>
    </div>
</main>

<style>
.markdown-editor {
    width: 100%;
    font-family: monospace;
    font-size: 14px;
    line-height: 1.5;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    resize: vertical;
}

.markdown-preview {
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
    min-height: 200px;
}

.form-help a {
    color: #007cba;
}
</style>

<script>
// Auto-update preview as user types
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('music_content');
    const preview = document.querySelector('.markdown-preview');
    
    if (editor) {
        editor.addEventListener('input', function() {
            // Simple client-side preview (limited markdown support)
            // For full markdown support, we'd need a client-side markdown parser
            // For now, we'll just show that the preview will update on save
        });
    }
});
</script>

<?php
include '../footer.php';
?>
