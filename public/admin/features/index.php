<?php
$title = "Features Page - Admin";

// Include image utility functions
require_once __DIR__ . '/../image_utils.php';

// Define the features data file path
$features_file = __DIR__ . '/../../data/features.json';

// Load features data from JSON file
$features_data = json_decode(file_get_contents($features_file), true);

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_feature'])) {
        $index_to_delete = (int)$_POST['feature_index'];
        if (isset($features_data[$index_to_delete])) {
            array_splice($features_data, $index_to_delete, 1);
        }
    } elseif (isset($_POST['add_feature'])) {
        $new_feature = [
            'title' => 'New Feature',
            'image' => 'https://placehold.co/600x400/111/fff?text=New+Feature',
            'link' => '#'
        ];
        $features_data[] = $new_feature;
    } else {
        // Handle updates and reordering
        $new_features = [];
        $order = isset($_POST['order']) && !empty($_POST['order']) ? explode(',', $_POST['order']) : array_keys($features_data);
        
        foreach ($order as $original_index) {
            $original_index = (int)$original_index;
            if (!isset($features_data[$original_index])) continue;
            
            $feature = [
                'title' => $_POST['feature_title'][$original_index] ?? $features_data[$original_index]['title'],
                'link' => $_POST['feature_link'][$original_index] ?? $features_data[$original_index]['link'],
                'image' => $features_data[$original_index]['image']
            ];

            // Image Upload for this feature
            $img_field = 'feature_image_' . $original_index;
            $uploaded_img = handle_image_upload($img_field, 'feature-item-' . $original_index, 'feature image');
            if ($uploaded_img) {
                $feature['image'] = '/' . $uploaded_img;
            }

            $new_features[] = $feature;
        }
        $features_data = $new_features;
    }

    // Save to JSON
    file_put_contents($features_file, json_encode($features_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $success_msg = "Features updated successfully!";
}

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Features & Interviews Settings</h1>
        
        <?php if (isset($success_msg)): ?>
            <div style="background: #e6fffa; border: 1px solid #38b2ac; color: #234e52; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="main-form">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <p>Manage the tiled grid of features and interviews.</p>
                <button type="submit" name="add_feature" class="btn btn-secondary">Add New Feature</button>
            </div>

            <div id="sortable-features" class="projects-admin-grid">
                <?php foreach ($features_data as $index => $feature): ?>
                <div class="project-admin-item" data-original-index="<?php echo $index; ?>" draggable="true">
                    <div class="drag-handle">⠿</div>
                    <div class="project-admin-content">
                        <div class="form-row" style="align-items: center;">
                            <div class="form-col" style="flex: 0 0 100px;">
                                <img src="<?php echo htmlspecialchars($feature['image']); ?>" id="feature-preview-<?php echo $index; ?>" class="admin-img-preview-sm" style="width: 100px; height: 60px;">
                                <input type="file" name="feature_image_<?php echo $index; ?>" accept="image/*" class="file-input-compact" onchange="previewImage(this, 'feature-preview-<?php echo $index; ?>')">
                            </div>
                            <div class="form-col">
                                <input type="text" name="feature_title[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($feature['title']); ?>" placeholder="Title">
                                <input type="text" name="feature_link[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($feature['link']); ?>" placeholder="Link URL">
                            </div>
                            <div class="form-col" style="flex: 0 0 50px;">
                                <button type="submit" name="delete_feature" value="<?php echo $index; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this feature?')">×</button>
                                <input type="hidden" name="feature_index" value="<?php echo $index; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="order" id="order-input" value="">

            <div style="margin-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-primary btn-lg">Save All Features</button>
            </div>
        </form>
    </div>
</main>

<style>
.projects-admin-grid { display: flex; flex-direction: column; gap: 1rem; }
.project-admin-item { 
    display: flex; align-items: center; gap: 1rem; 
    background: #fff; border: 1px solid #ddd; padding: 1rem; border-radius: 8px;
    cursor: grab;
}
.drag-handle { color: #ccc; font-size: 1.5rem; }
.project-admin-content { flex-grow: 1; }
.admin-img-preview-sm { object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
.form-row { display: flex; gap: 1rem; }
.form-col { flex: 1; }
.file-input-compact { font-size: 0.7rem; width: 100%; margin-top: 0.25rem; }
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
    const container = document.getElementById('sortable-features');
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
