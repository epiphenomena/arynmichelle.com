<?php
$title = "Photo Gallery - Admin";

// Include image utility functions
require_once __DIR__ . '/../../image_utils.php';

// Define the data file path
$data_file = __DIR__ . '/../../../data/gallery.json';

// Load data from JSON file
$gallery_data = json_decode(file_get_contents($data_file), true);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_photo'])) {
        $index = (int)$_POST['photo_index'];
        if (isset($gallery_data[$index])) {
            array_splice($gallery_data, $index, 1);
        }
    } elseif (isset($_POST['add_photo'])) {
        // Handle photo upload
        if (isset($_FILES['new_photo']) && $_FILES['new_photo']['error'] == 0) {
            $uploaded_img = handle_image_upload('new_photo', 'gallery-photo-' . time(), 'gallery photo');
            if ($uploaded_img) {
                $gallery_data[] = [
                    'image' => '/' . $uploaded_img,
                    'caption' => $_POST['new_caption'] ?? ''
                ];
            } else {
                $error_msg = "Failed to upload photo.";
            }
        }
    } else {
        // Update captions and order
        $new_data = [];
        $order = isset($_POST['order']) && !empty($_POST['order']) ? explode(',', $_POST['order']) : array_keys($gallery_data);
        
        foreach ($order as $idx) {
            $idx = (int)$idx;
            if (!isset($gallery_data[$idx])) continue;
            
            $item = [
                'image' => $gallery_data[$idx]['image'],
                'caption' => $_POST['caption'][$idx] ?? $gallery_data[$idx]['caption']
            ];
            $new_data[] = $item;
        }
        $gallery_data = $new_data;
    }

    file_put_contents($data_file, json_encode($gallery_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $success_msg = "Gallery updated successfully!";
}

include '../../header.php';
?>

<main>
    <div class="container">
        <h1>Photo Gallery Settings</h1>
        
        <?php if (isset($error_msg)): ?>
            <div style="background: #fee; border: 1px solid #fcc; color: #a00; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $error_msg; ?>
            </div>
        <?php elseif (isset($success_msg)): ?>
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

        <section class="admin-section">
            <h2>Add New Photo</h2>
            <div class="admin-card">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-col">
                            <label>Photo File</label>
                            <input type="file" name="new_photo" accept="image/*" required>
                        </div>
                        <div class="form-col">
                            <label>Caption (Optional)</label>
                            <input type="text" name="new_caption" placeholder="Enter caption...">
                        </div>
                        <div class="form-col" style="flex: 0 0 150px; display: flex; align-items: flex-end;">
                            <button type="submit" name="add_photo" class="btn btn-secondary">Upload Photo</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-section">
            <h2>Manage Gallery Photos</h2>
            <p>Drag to reorder photos. Click 'Save Changes' to update captions and order.</p>
            
            <form method="post">
                <div id="sortable-gallery" class="gallery-admin-grid">
                    <?php foreach ($gallery_data as $index => $photo): ?>
                    <div class="gallery-admin-item" data-original-index="<?php echo $index; ?>" draggable="true">
                        <div class="drag-handle">⠿</div>
                        <div class="gallery-admin-content">
                            <div class="form-row" style="align-items: center;">
                                <div class="form-col" style="flex: 0 0 100px;">
                                    <img src="<?php echo htmlspecialchars($photo['image']); ?>" class="admin-img-preview-sm" style="width:100px; height:100px;">
                                </div>
                                <div class="form-col">
                                    <input type="text" name="caption[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($photo['caption']); ?>" placeholder="Caption">
                                </div>
                                <div class="form-col" style="flex: 0 0 50px;">
                                    <button type="submit" name="delete_photo" value="<?php echo $index; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this photo?')">×</button>
                                    <input type="hidden" name="photo_index" value="<?php echo $index; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="order" id="order-input" value="">
                
                <div style="margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 2rem; text-align: right;">
                    <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                </div>
            </form>
        </section>
    </div>
</main>

<style>
.admin-section { margin-bottom: 3rem; }
.admin-card { background: #fff; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; }
.gallery-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1rem; }
.gallery-admin-item { 
    display: flex; align-items: center; gap: 1rem; 
    background: #fff; border: 1px solid #ddd; padding: 1rem; border-radius: 8px;
    cursor: grab;
}
.drag-handle { color: #ccc; font-size: 1.5rem; }
.gallery-admin-content { flex-grow: 1; }
.form-row { display: flex; gap: 1rem; }
.form-col { flex: 1; }
.admin-img-preview-sm { object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sortable-gallery');
    if (!container) return;

    let draggedItem = null;

    container.querySelectorAll('.gallery-admin-item').forEach(item => {
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
            const afterElement = getDragAfterElement(container, e.clientX);
            if (afterElement == null) container.appendChild(draggedItem);
            else container.insertBefore(draggedItem, afterElement);
            updateOrder();
        }
    });

    function getDragAfterElement(container, x) {
        const draggableElements = [...container.querySelectorAll('.gallery-admin-item:not(:hover)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;
            if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
            else return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateOrder() {
        const order = [...container.querySelectorAll('.gallery-admin-item')].map(item => item.dataset.originalIndex);
        document.getElementById('order-input').value = order.join(',');
    }
});
</script>

<?php include '../../footer.php'; ?>
