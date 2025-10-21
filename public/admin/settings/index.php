<?php
$title = "Settings - Quality Custom Displays";

// Include image utility functions
require_once __DIR__ . '/../image_utils.php';

// Define the settings file path
$settings_file = __DIR__ . '/../../data/settings.json';

// Load settings from JSON file
$settings_data = json_decode(file_get_contents($settings_file), true);
$settings = $settings_data;

// Process form submission if POST data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['site-title'] = $_POST['site-title'];
    $settings['site-description'] = $_POST['site-description'];
    $settings['contact-email'] = $_POST['contact-email'];
    $settings['phone-number'] = $_POST['phone-number'];
    $settings['street-address'] = $_POST['street-address'];

    // Handle social media card image upload
    $new_image_path = handle_image_upload('social-media-card', 'social-card-image', 'social media card');
    if ($new_image_path) {
        // Remove old image if it exists and is different from the new one
        if (!empty($settings['social-media-card']) && $settings['social-media-card'] !== $new_image_path) {
            remove_image_file($settings['social-media-card']);
        }
        $settings['social-media-card'] = $new_image_path;
    } elseif (isset($_POST['remove-social-image']) && $_POST['remove-social-image'] === '1') {
        // Remove existing image if requested
        if (!empty($settings['social-media-card'])) {
            remove_image_file($settings['social-media-card']);
            $settings['social-media-card'] = '';
        }
    }

    // Ensure data directory exists
    $data_dir = __DIR__ . '/../../data/';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }

    // Save settings to JSON file
    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));

    // Refresh settings after saving
    $settings_data = json_decode(file_get_contents($settings_file), true);
    $settings = $settings_data;
}

include '../header.php';
?>

<main>
    <div class="container">
        <h1>Website Settings</h1>
        <p>Configure global website settings.</p>

        <form method="post" enctype="multipart/form-data" id="main-form">
            <div class="form-group">
                <label for="site-title">Site Title</label>
                <input type="text" id="site-title" name="site-title" placeholder="Quality Custom Displays" value="<?php echo htmlspecialchars($settings['site-title']); ?>">
            </div>

            <div class="form-group">
                <label for="site-description">Site Description</label>
                <textarea id="site-description" name="site-description" placeholder="Custom cabinets and displays crafted to perfection."><?php echo htmlspecialchars($settings['site-description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="contact-email">Contact Email</label>
                <input type="email" id="contact-email" name="contact-email" placeholder="info@qualitycustomdisplays.com" value="<?php echo htmlspecialchars($settings['contact-email']); ?>">
            </div>

            <div class="form-group">
                <label for="phone-number">Phone Number</label>
                <input type="text" id="phone-number" name="phone-number" placeholder="(555) 123-4567" value="<?php echo htmlspecialchars($settings['phone-number']); ?>">
            </div>

            <div class="form-group">
                <label for="street-address">Street Address</label>
                <input type="text" id="street-address" name="street-address" placeholder="123 Main Street, City, State 12345" value="<?php echo htmlspecialchars($settings['street-address']); ?>">
            </div>

            <div class="form-group">
                <label for="social-media-card">Social Media Card Image</label>
                <input type="file" id="social-media-card" name="social-media-card" accept="image/*">
                <small class="form-help">Upload an image for social media previews (JPG, PNG, GIF). Recommended size: 1200x630 pixels.</small>

                <?php if (!empty($settings['social-media-card'])): ?>
                    <div class="image-preview">
                        <p>Current image:</p>
                        <img src="/media/<?php echo $settings['social-media-card']; ?>" alt="Current social media card" class="social-card-preview" style="max-width: 300px; max-height: 200px;">
                        <div>
                            <button type="submit" name="remove-social-image" value="1" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to remove this image?')">Remove Image</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="image-preview">
                        <p>Current image:</p>
                        <img src="" alt="Social media card preview" class="social-card-preview" style="max-width: 300px; max-height: 200px; display: none;">
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</main>

<?php
include '../footer.php';
?>