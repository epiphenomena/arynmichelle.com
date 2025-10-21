<?php
$title = "Home Page Quadrants - Admin";

// Define the home data file path
$home_file = __DIR__ . '/../../data/home.json';

// Load home data from JSON file
$home_data = json_decode(file_get_contents($home_file), true);
$quadrants = $home_data['quadrants'];

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quadrants = [];
    for ($i = 0; $i < 4; $i++) {
        $quadrants[] = [
            'title' => $_POST['title'][$i] ?? '',
            'subtitle' => $_POST['subtitle'][$i] ?? '',
            'link' => $_POST['link'][$i] ?? ''
        ];
    }
    
    $home_data['quadrants'] = $quadrants;
    
    // Ensure data directory exists
    $data_dir = __DIR__ . '/../../data/';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // Save home data to JSON file
    file_put_contents($home_file, json_encode($home_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Refresh data after saving
    $home_data = json_decode(file_get_contents($home_file), true);
    $quadrants = $home_data['quadrants'];
}

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Home Page Quadrants</h1>
        <p>Manage the content for the four quadrants on the home page.</p>

        <form method="post" id="quadrants-form">
            <?php foreach ($quadrants as $index => $quadrant): ?>
            <div class="form-group quadrant-group">
                <h3>Quadrant <?php echo $index + 1; ?></h3>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="title_<?php echo $index; ?>">Title</label>
                        <input type="text" id="title_<?php echo $index; ?>" name="title[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($quadrant['title']); ?>" required>
                    </div>
                    <div class="form-col">
                        <label for="link_<?php echo $index; ?>">Link URL</label>
                        <input type="text" id="link_<?php echo $index; ?>" name="link[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($quadrant['link']); ?>">
                        <small class="form-help">Leave blank or use 'about/' for default about page link</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-full">
                        <label for="subtitle_<?php echo $index; ?>">Subtitle</label>
                        <input type="text" id="subtitle_<?php echo $index; ?>" name="subtitle[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($quadrant['subtitle']); ?>" required>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary">Save Quadrants</button>
        </form>
    </div>
</main>

<style>
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

.quadrant-group {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    background-color: #f9f9f9;
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}
</style>

<?php
include '../footer.php';
?>