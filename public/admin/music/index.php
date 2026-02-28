<?php
$title = "Music Page - Admin";

// Include image utility functions
require_once __DIR__ . '/../image_utils.php';

// Define the music data file path
$music_file = __DIR__ . '/../../data/music.json';

// Load music data from JSON file
$music_data = json_decode(file_get_contents($music_file), true);

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle Latest Album Updates
    $music_data['latest_album']['title'] = $_POST['latest_title'] ?? '';
    $music_data['latest_album']['description'] = $_POST['latest_description'] ?? '';
    $music_data['latest_album']['spotify_url'] = $_POST['latest_spotify'] ?? '';
    $music_data['latest_album']['apple_music_url'] = $_POST['latest_apple'] ?? '';
    $music_data['latest_album']['youtube_url'] = $_POST['latest_youtube'] ?? '';

    // Latest Album Image Upload
    if (isset($_FILES['latest_image']) && $_FILES['latest_image']['error'] == 0) {
        $latest_image = handle_image_upload('latest_image', 'latest-album-cover', 'latest album cover');
        if ($latest_image) {
            $music_data['latest_album']['cover_image'] = '/' . $latest_image;
        } else {
            $error_msg = "Failed to upload latest album cover. Check file type and size.";
        }
    } elseif (isset($_FILES['latest_image']) && $_FILES['latest_image']['error'] != 4) {
        $error_code = $_FILES['latest_image']['error'];
        if ($error_code == 1) {
            $error_msg = "The uploaded file is too large. Current limit: " . ini_get('upload_max_filesize');
        } else {
            $error_msg = "Upload error: " . $error_code;
        }
    }

    // 2. Handle Previous Projects
    if (isset($_POST['delete_project'])) {
        $index_to_delete = (int)$_POST['project_index'];
        if (isset($music_data['previous_projects'][$index_to_delete])) {
            array_splice($music_data['previous_projects'], $index_to_delete, 1);
        }
    } elseif (isset($_POST['add_project'])) {
        $new_project = [
            'title' => 'New Project',
            'cover_image' => 'https://placehold.co/600x600/111/fff?text=New+Project',
            'link' => '#'
        ];
        $music_data['previous_projects'][] = $new_project;
    } else {
        // Update existing projects
        $projects = [];
        $order = isset($_POST['order']) && !empty($_POST['order']) ? explode(',', $_POST['order']) : array_keys($music_data['previous_projects']);
        
        foreach ($order as $original_index) {
            $original_index = (int)$original_index;
            if (!isset($music_data['previous_projects'][$original_index])) continue;
            
            $project = [
                'title' => $_POST['project_title'][$original_index] ?? $music_data['previous_projects'][$original_index]['title'],
                'link' => $_POST['project_link'][$original_index] ?? $music_data['previous_projects'][$original_index]['link'],
                'cover_image' => $music_data['previous_projects'][$original_index]['cover_image']
            ];

            // Handle individual project image uploads
            $img_field = 'project_image_' . $original_index;
            $uploaded_img = handle_image_upload($img_field, 'project-cover-' . $original_index, 'project cover');
            if ($uploaded_img) {
                $project['cover_image'] = '/' . $uploaded_img;
            }

            $projects[] = $project;
        }
        $music_data['previous_projects'] = $projects;
    }

    // Save music data to JSON file
    file_put_contents($music_file, json_encode($music_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Refresh data after saving
    $music_data = json_decode(file_get_contents($music_file), true);
    $success_msg = !isset($error_msg) ? "Music settings saved successfully!" : "";
}

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Music Page Settings</h1>
        
        <?php if (isset($error_msg)): ?>
            <div style="background: #fee; border: 1px solid #fcc; color: #a00; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $error_msg; ?>
            </div>
        <?php elseif (isset($success_msg) && $success_msg !== ""): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toast = document.getElementById('toast');
                    if (toast) {
                        toast.textContent = "<?php echo $success_msg; ?>";
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                });
            </script>
        <?php endif; ?>

        <p>Manage the discography layout, latest release, and previous projects.</p>

        <form method="post" enctype="multipart/form-data" id="main-form">
            <!-- Latest Album Hero Section -->
            <section class="admin-section">
                <h2>Latest Release (Hero Section)</h2>
                <div class="admin-card">
                    <div class="form-row">
                        <div class="form-col" style="flex: 0 0 200px;">
                            <label>Cover Image</label>
                            <div class="image-preview-container">
                                <img src="<?php echo htmlspecialchars($music_data['latest_album']['cover_image']); ?>" id="latest-preview" class="admin-img-preview">
                                <input type="file" name="latest_image" accept="image/*" class="file-input-compact" onchange="previewImage(this, 'latest-preview')">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="latest_title">Album Title</label>
                                <input type="text" id="latest_title" name="latest_title" value="<?php echo htmlspecialchars($music_data['latest_album']['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="latest_description">Brief Description</label>
                                <textarea id="latest_description" name="latest_description" rows="3"><?php echo htmlspecialchars($music_data['latest_album']['description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="streaming-links">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Spotify URL</label>
                                <input type="text" name="latest_spotify" value="<?php echo htmlspecialchars($music_data['latest_album']['spotify_url']); ?>">
                            </div>
                            <div class="form-col">
                                <label>Apple Music URL</label>
                                <input type="text" name="latest_apple" value="<?php echo htmlspecialchars($music_data['latest_album']['apple_music_url']); ?>">
                            </div>
                            <div class="form-col">
                                <label>YouTube URL</label>
                                <input type="text" name="latest_youtube" value="<?php echo htmlspecialchars($music_data['latest_album']['youtube_url']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Discography Grid -->
            <section class="admin-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Discography Grid</h2>
                    <button type="submit" name="add_project" class="btn btn-secondary">Add New Project</button>
                </div>
                
                <div id="sortable-projects" class="projects-admin-grid">
                    <?php foreach ($music_data['previous_projects'] as $index => $project): ?>
                    <div class="project-admin-item" data-original-index="<?php echo $index; ?>" draggable="true">
                        <div class="drag-handle">⠿</div>
                        <div class="project-admin-content">
                            <div class="form-row" style="align-items: center;">
                                <div class="form-col" style="flex: 0 0 80px;">
                                    <img src="<?php echo htmlspecialchars($project['cover_image']); ?>" id="project-preview-<?php echo $index; ?>" class="admin-img-preview-sm">
                                    <input type="file" name="project_image_<?php echo $index; ?>" accept="image/*" class="file-input-compact" onchange="previewImage(this, 'project-preview-<?php echo $index; ?>')">
                                </div>
                                <div class="form-col">
                                    <input type="text" name="project_title[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($project['title']); ?>" placeholder="Project Title">
                                    <input type="text" name="project_link[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($project['link']); ?>" placeholder="Link (Bandcamp, etc)">
                                </div>
                                <div class="form-col" style="flex: 0 0 50px;">
                                    <button type="submit" name="delete_project" value="<?php echo $index; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this project?')">×</button>
                                    <input type="hidden" name="project_index" value="<?php echo $index; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="order" id="order-input" value="">
            </section>

            <div style="margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-primary btn-lg">Save All Music Settings</button>
            </div>
        </form>
    </div>
</main>

<style>
.admin-section { margin-bottom: 3rem; }
.admin-card { background: #fff; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; }
.admin-img-preview { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem; border: 1px solid #eee; }
.admin-img-preview-sm { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
.file-input-compact { font-size: 0.7rem; width: 100%; }
.form-row { display: flex; gap: 1rem; }
.form-col { flex: 1; }
.streaming-links { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
.projects-admin-grid { display: flex; flex-direction: column; gap: 1rem; }
.project-admin-item { 
    display: flex; align-items: center; gap: 1rem; 
    background: #fff; border: 1px solid #ddd; padding: 1rem; border-radius: 8px;
    cursor: grab;
}
.drag-handle { color: #ccc; font-size: 1.5rem; }
.project-admin-content { flex-grow: 1; }
</style>

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

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sortable-projects');
    if (!container) return;

    let draggedItem = null;

    container.querySelectorAll('.project-admin-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            setTimeout(() => this.style.opacity = '0.5', 0);
        });
        item.addEventListener('dragend', function() {
            this.style.opacity = '1';
            draggedItem = null;
        });
    });

    container.addEventListener('dragover', e => e.preventDefault());
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        if (draggedItem) {
            const afterElement = getDragAfterElement(container, e.clientY);
            if (afterElement == null) container.appendChild(draggedItem);
            else container.insertBefore(draggedItem, afterElement);
            updateOrder();
            document.getElementById('save-btn').disabled = false;
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.project-admin-item:not(:hover)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
            else return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateOrder() {
        const order = [...container.querySelectorAll('.project-admin-item')].map(item => item.dataset.originalIndex);
        document.getElementById('order-input').value = order.join(',');
    }
});
</script>

<?php include '../footer.php'; ?>
