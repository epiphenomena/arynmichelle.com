<?php
$title = "About Page - Admin";

// Define the about content file path
$about_file = __DIR__ . '/../../data/about.md';

// Load about content from markdown file
$about_content = file_exists($about_file) ? file_get_contents($about_file) : "# About Page\n\nYour about page content goes here.";

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $about_content = $_POST['about_content'] ?? '';
    
    // Ensure data directory exists
    $data_dir = __DIR__ . '/../../data/';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // Save about content to markdown file
    file_put_contents($about_file, $about_content);
    
    // Refresh content after saving
    $about_content = file_get_contents($about_file);
}

// Include Parsedown for preview
require_once __DIR__ . '/../../Parsedown.php';
$parsedown = new Parsedown();
$preview_content = $parsedown->text($about_content);

include '../header.php';
?>

<main>
    <div class="container">
        <h1>About Page Content</h1>
        <p>Edit the content for the about page using Markdown syntax.</p>

        <form method="post" id="about-form">
            <div class="form-group">
                <label for="about_content">Content (Markdown)</label>
                <textarea id="about_content" name="about_content" rows="20" class="markdown-editor"><?php echo htmlspecialchars($about_content); ?></textarea>
                <small class="form-help">Use Markdown syntax for formatting. <a href="https://www.markdownguide.org/cheat-sheet/" target="_blank">Markdown Cheat Sheet</a></small>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Content</button>
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
    const editor = document.getElementById('about_content');
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