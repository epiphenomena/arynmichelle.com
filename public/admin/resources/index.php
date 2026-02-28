<?php
$title = "Resources Page - Admin";

// Include image utility functions
require_once __DIR__ . '/../image_utils.php';

// Define the resources data file path
$resources_file = __DIR__ . '/../../data/resources.json';

// Load resources data from JSON file
$resources_data = json_decode(file_get_contents($resources_file), true);

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_data = [];
    
    foreach ($_POST['section_title'] as $index => $title) {
        $section = [
            'title' => $title,
            'image' => $resources_data[$index]['image'],
            'links' => []
        ];
        
        // Handle links
        if (isset($_POST['link_text'][$index])) {
            foreach ($_POST['link_text'][$index] as $link_idx => $text) {
                if (!empty($text)) {
                    $section['links'][] = [
                        'text' => $text,
                        'url' => $_POST['link_url'][$index][$link_idx] ?? '#'
                    ];
                }
            }
        }
        
        // Handle Image Upload for this section
        $img_field = 'section_image_' . $index;
        $uploaded_img = handle_image_upload($img_field, 'resource-' . strtolower(str_replace(' ', '-', $title)), 'resource image');
        if ($uploaded_img) {
            $section['image'] = '/' . $uploaded_img;
        }
        
        $new_data[] = $section;
    }
    
    $resources_data = $new_data;
    
    // Save to JSON
    file_put_contents($resources_file, json_encode($resources_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $success_msg = "Resources updated successfully!";
}

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Resources Page Settings</h1>
        
        <?php if (isset($success_msg)): ?>
            <div style="background: #e6fffa; border: 1px solid #38b2ac; color: #234e52; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="main-form">
            <?php foreach ($resources_data as $index => $section): ?>
                <section class="admin-section admin-card" style="margin-bottom: 3rem;">
                    <h2>Section <?php echo $index + 1; ?>: <?php echo htmlspecialchars($section['title']); ?></h2>
                    
                    <div class="form-row">
                        <div class="form-col" style="flex: 0 0 250px;">
                            <label>Section Image</label>
                            <img src="<?php echo htmlspecialchars($section['image']); ?>" id="preview-<?php echo $index; ?>" class="admin-img-preview" style="width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 0.5rem;">
                            <input type="file" name="section_image_<?php echo $index; ?>" accept="image/*" class="file-input-compact" onchange="previewImage(this, 'preview-<?php echo $index; ?>')">
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="section_title[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($section['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Links</label>
                                <div id="links-container-<?php echo $index; ?>">
                                    <?php foreach ($section['links'] as $link_idx => $link): ?>
                                        <div class="link-entry" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <input type="text" name="link_text[<?php echo $index; ?>][]" value="<?php echo htmlspecialchars($link['text']); ?>" placeholder="Link Text" style="flex: 1;">
                                            <input type="text" name="link_url[<?php echo $index; ?>][]" value="<?php echo htmlspecialchars($link['url']); ?>" placeholder="URL" style="flex: 2;">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addLink(<?php echo $index; ?>)">+ Add Link</button>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>

            <div style="margin-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-primary btn-lg">Save Resources</button>
            </div>
        </form>
    </div>
</main>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        document.getElementById('save-btn').disabled = false;
    }
}

function addLink(sectionIndex) {
    const container = document.getElementById('links-container-' + sectionIndex);
    const div = document.createElement('div');
    div.className = 'link-entry';
    div.style.display = 'flex';
    div.style.gap = '0.5rem';
    div.style.marginBottom = '0.5rem';
    div.innerHTML = `
        <input type="text" name="link_text[${sectionIndex}][]" placeholder="Link Text" style="flex: 1;">
        <input type="text" name="link_url[${sectionIndex}][]" placeholder="URL" style="flex: 2;">
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
    document.getElementById('save-btn').disabled = false;
}
</script>

<style>
.admin-card { background: #fff; border: 1px solid #ddd; padding: 2rem; border-radius: 12px; }
.form-row { display: flex; gap: 2rem; }
.form-col { flex: 1; }
.file-input-compact { font-size: 0.8rem; }
</style>

<?php include '../footer.php'; ?>
