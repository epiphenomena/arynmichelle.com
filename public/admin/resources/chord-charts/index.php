<?php
$title = "Chord Charts - Admin";

// Include image utility functions
require_once __DIR__ . '/../../image_utils.php';

// Define the data file path
$data_file = __DIR__ . '/../../../data/chord-charts.json';

// Load data from JSON file
$charts_data = json_decode(file_get_contents($data_file), true);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_chart'])) {
        $index = (int)$_POST['chart_index'];
        if (isset($charts_data[$index])) {
            array_splice($charts_data, $index, 1);
        }
    } elseif (isset($_POST['add_chart'])) {
        $charts_data[] = [
            'title' => 'New Album',
            'cover_image' => 'https://placehold.co/600x600/111/fff?text=New+Album',
            'download_url' => ''
        ];
    } else {
        $new_data = [];
        $order = isset($_POST['order']) && !empty($_POST['order']) ? explode(',', $_POST['order']) : array_keys($charts_data);
        
        foreach ($order as $idx) {
            $idx = (int)$idx;
            if (!isset($charts_data[$idx])) continue;
            
            $item = [
                'title' => $_POST['title'][$idx] ?? $charts_data[$idx]['title'],
                'cover_image' => $charts_data[$idx]['cover_image'],
                'download_url' => $_POST['download_url'][$idx] ?? $charts_data[$idx]['download_url']
            ];

            // Handle cover image upload
            $img_field = 'cover_image_' . $idx;
            if (isset($_FILES[$img_field]) && $_FILES[$img_field]['error'] == 0) {
                $uploaded_img = handle_image_upload($img_field, 'album-cover-' . $idx, 'album cover');
                if ($uploaded_img) {
                    $item['cover_image'] = '/' . $uploaded_img;
                }
            }

            // Handle PDF upload
            $pdf_field = 'pdf_file_' . $idx;
            if (isset($_FILES[$pdf_field]) && $_FILES[$pdf_field]['error'] == 0) {
                $upload_dir = 'media/chord-charts/';
                $full_upload_dir = __DIR__ . '/../../../' . $upload_dir;
                if (!file_exists($full_upload_dir)) {
                    mkdir($full_upload_dir, 0755, true);
                }
                
                $filename = 'charts-' . $idx . '-' . time() . '.pdf';
                $target_path = $full_upload_dir . $filename;
                
                if (move_uploaded_file($_FILES[$pdf_field]['tmp_name'], $target_path)) {
                    $item['download_url'] = '/' . $upload_dir . $filename;
                }
            }

            $new_data[] = $item;
        }
        $charts_data = $new_data;
    }

    file_put_contents($data_file, json_encode($charts_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $success_msg = "Chord charts updated successfully!";
}

include '../../header.php';
?>

<main>
    <div class="container">
        <h1>Chord Charts Settings</h1>
        
        <?php if (isset($success_msg)): ?>
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

        <form method="post" enctype="multipart/form-data">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <p>Manage album chord chart downloads.</p>
                <button type="submit" name="add_chart" class="btn btn-secondary">Add New Album</button>
            </div>

            <div id="sortable-charts" class="projects-admin-grid">
                <?php foreach ($charts_data as $index => $album): ?>
                <div class="project-admin-item" data-original-index="<?php echo $index; ?>" draggable="true">
                    <div class="drag-handle">⠿</div>
                    <div class="project-admin-content">
                        <div class="form-row">
                            <div class="form-col" style="flex: 0 0 100px;">
                                <img src="<?php echo htmlspecialchars($album['cover_image']); ?>" id="preview-<?php echo $index; ?>" class="admin-img-preview-sm" style="width:100px; height:100px;">
                                <input type="file" name="cover_image_<?php echo $index; ?>" accept="image/*" class="file-input-compact" onchange="previewImage(this, 'preview-<?php echo $index; ?>')">
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label>Album Title</label>
                                    <input type="text" name="title[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($album['title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Current Download URL</label>
                                    <input type="text" name="download_url[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($album['download_url']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Upload New PDF / ZIP</label>
                                    <input type="file" name="pdf_file_<?php echo $index; ?>" accept=".pdf,.zip">
                                </div>
                            </div>
                            <div class="form-col" style="flex: 0 0 50px; display:flex; align-items:center;">
                                <button type="submit" name="delete_chart" value="<?php echo $index; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this album?')">×</button>
                                <input type="hidden" name="chart_index" value="<?php echo $index; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <input type="hidden" name="order" id="order-input" value="">
            
            <div style="margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 2rem; text-align: right;">
                <button type="submit" class="btn btn-primary btn-lg">Save All Charts</button>
            </div>
        </form>
    </div>
</main>

<style>
.projects-admin-grid { display: flex; flex-direction: column; gap: 1rem; }
.project-admin-item { 
    display: flex; align-items: center; gap: 1rem; 
    background: #fff; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px;
    cursor: grab;
}
.drag-handle { color: #ccc; font-size: 1.5rem; }
.project-admin-content { flex-grow: 1; }
.form-row { display: flex; gap: 1.5rem; }
.form-col { flex: 1; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.3rem; font-size: 0.85rem; font-weight: bold; }
.admin-img-preview-sm { object-fit: cover; border-radius: 4px; border: 1px solid #eee; margin-bottom: 0.5rem; }
.file-input-compact { font-size: 0.7rem; width: 100%; }
</style>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sortable-charts');
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

<?php include '../../footer.php'; ?>
