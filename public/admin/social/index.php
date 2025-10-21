<?php
$title = "Social Media Links - Admin";

// Define the social data file path
$social_file = __DIR__ . '/../../data/social.json';

// Load social data from JSON file
$social_data = json_decode(file_get_contents($social_file), true);
$social_links = $social_data['social_links'];

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_link'])) {
        // Add a new social link
        $new_link = [
            'type' => $_POST['new_type'] ?? 'spotify',
            'url' => $_POST['new_url'] ?? ''
        ];
        $social_data['social_links'][] = $new_link;
    } elseif (isset($_POST['delete_link'])) {
        // Delete a social link
        $index_to_delete = (int)$_POST['link_index'];
        if (isset($social_data['social_links'][$index_to_delete])) {
            array_splice($social_data['social_links'], $index_to_delete, 1);
        }
    } elseif (isset($_POST['update_links'])) {
        // Update existing links
        $updated_links = [];
        $count = count($_POST['type'] ?? []);
        for ($i = 0; $i < $count; $i++) {
            $updated_links[] = [
                'type' => $_POST['type'][$i] ?? 'spotify',
                'url' => $_POST['url'][$i] ?? ''
            ];
        }
        $social_data['social_links'] = $updated_links;
    }
    
    // Ensure data directory exists
    $data_dir = __DIR__ . '/../../data/';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // Save social data to JSON file
    file_put_contents($social_file, json_encode($social_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Refresh data after saving
    $social_data = json_decode(file_get_contents($social_file), true);
    $social_links = $social_data['social_links'];
}

include '../header.php';

// Define available social media types
$social_types = [
    'spotify' => 'Spotify',
    'youtube' => 'YouTube',
    'youtube_music' => 'YouTube Music',
    'x' => 'X (Twitter)',
    'instagram' => 'Instagram',
    'tiktok' => 'TikTok',
    'facebook' => 'Facebook',
    'apple_music' => 'Apple Music'
];
?>

<main>
    <div class="container">
        <h1>Social Media Links</h1>
        <p>Manage the social media links displayed on the site.</p>

        <!-- Add new social link form -->
        <div class="form-group" style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; background-color: #f0f8ff;">
            <h3>Add New Social Link</h3>
            <form method="post" style="display: flex; gap: 1rem; align-items: end;">
                <div class="form-col">
                    <label for="new_type">Social Media Type</label>
                    <select id="new_type" name="new_type">
                        <?php foreach ($social_types as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-col">
                    <label for="new_url">URL</label>
                    <input type="url" id="new_url" name="new_url" placeholder="https://...">
                </div>
                <button type="submit" name="add_link" class="btn btn-secondary" style="height: fit-content;">Add Link</button>
            </form>
        </div>

        <!-- Edit existing social links form -->
        <form method="post" id="social-links-form">
            <h3>Manage Social Links</h3>
            <p>Drag and drop to reorder. The order in the list will be the order displayed on the site.</p>
            
            <?php if (!empty($social_links)): ?>
                <div id="sortable-links" class="social-links-container">
                    <?php foreach ($social_links as $index => $link): ?>
                    <div class="social-link-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background-color: #f9f9f9; display: flex; gap: 1rem; align-items: start;">
                        <div style="flex: 1;">
                            <div class="form-row">
                                <div class="form-col">
                                    <label for="type_<?php echo $index; ?>">Social Media Type</label>
                                    <select id="type_<?php echo $index; ?>" name="type[<?php echo $index; ?>]">
                                        <?php foreach ($social_types as $type => $label): ?>
                                            <option value="<?php echo $type; ?>" <?php echo ($link['type'] == $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-col" style="flex: 2;">
                                    <label for="url_<?php echo $index; ?>">URL</label>
                                    <input type="url" id="url_<?php echo $index; ?>" name="url[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($link['url']); ?>" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                        <div>
                            <button type="submit" name="delete_link" value="<?php echo $index; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this social link?')" style="height: fit-content;">Delete</button>
                            <input type="hidden" name="link_index" value="<?php echo $index; ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No social links added yet.</p>
            <?php endif; ?>
            
            <?php if (!empty($social_links)): ?>
                <button type="submit" name="update_links" class="btn btn-primary">Save Changes</button>
            <?php endif; ?>
        </form>
    </div>
</main>

<style>
.social-links-container {
    min-height: 100px;
}

.social-link-item {
    cursor: move;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-col {
    flex: 1;
}

.form-col-full {
    flex: 1;
    width: 100%;
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}
</style>

<script>
// Simple drag-and-drop reordering (without jQuery)
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sortable-links');
    if (!container) return;
    
    let draggedItem = null;
    
    container.querySelectorAll('.social-link-item').forEach(item => {
        item.setAttribute('draggable', true);
        
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            setTimeout(() => this.style.opacity = '0.7', 0);
        });
        
        item.addEventListener('dragend', function() {
            this.style.opacity = '1';
            draggedItem = null;
        });
    });
    
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#f0f0f0';
    });
    
    container.addEventListener('dragleave', function() {
        this.style.backgroundColor = '';
    });
    
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '';
        
        if (draggedItem) {
            const afterElement = getDragAfterElement(container, e.clientY);
            if (afterElement == null) {
                container.appendChild(draggedItem);
            } else {
                container.insertBefore(draggedItem, afterElement);
            }
        }
    });
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.social-link-item:not(:hover)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});
</script>

<?php
include '../footer.php';
?>