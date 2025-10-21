<?php
/**
 * Utility functions for handling image uploads
 */

/**
 * Handles image upload and replacement
 * 
 * @param string $file_input_name The name of the file input field in $_FILES
 * @param string $filename The desired filename without extension (e.g. 'social-card-image')
 * @param string $description Description for the image context (e.g. 'social media card')
 * @param array $allowed_types Array of allowed MIME types
 * @param string $upload_dir Directory to upload to (relative to project root)
 * @return string|bool Returns the path to the uploaded image, or false on failure
 */
function handle_image_upload($file_input_name, $filename, $description, $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], $upload_dir = 'media/') {
    // Check if file was uploaded without errors
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] != 0) {
        return false;
    }
    
    // Get file info
    $file_type = $_FILES[$file_input_name]['type'];
    $file_extension = pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION);
    
    // Check if file type is allowed
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    // Ensure upload directory exists
    $full_upload_path = __DIR__ . '/../' . $upload_dir;
    if (!is_dir($full_upload_path)) {
        mkdir($full_upload_path, 0755, true);
    }
    
    // Create filename with the provided name and the original extension
    $final_filename = $filename . '.' . $file_extension;
    $upload_path = $full_upload_path . $final_filename;
    
    // Get the absolute path for the old file to remove it if it exists
    $old_file_path = $upload_dir . $filename . '.*'; // Wildcard to match any extension
    $existing_files = glob(__DIR__ . '/../' . $old_file_path);
    
    // Remove any existing files with the same base name but different extensions
    foreach ($existing_files as $existing_file) {
        if (file_exists($existing_file) && realpath($existing_file) !== realpath($upload_path)) {
            unlink($existing_file);
        }
    }
    
    // Move the uploaded file to the destination
    if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $upload_path)) {
        return $upload_dir . $final_filename;
    }
    
    return false;
}

/**
 * Removes an existing image file
 * 
 * @param string $current_image_path Path to the current image
 * @return bool True if the file was removed or didn't exist, false on failure
 */
function remove_image_file($current_image_path) {
    if (empty($current_image_path)) {
        return true; // Nothing to remove
    }
    
    $full_path = __DIR__ . '/../' . $current_image_path;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    
    return true; // File doesn't exist, so nothing to remove
}