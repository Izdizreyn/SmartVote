<?php
/**
 * File Upload Handler for Candidate Photos
 */

function handleFileUpload($file, $upload_dir = 'uploads/') {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF images are allowed'];
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB'];
    }

    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true, 
            'message' => 'File uploaded successfully',
            'filename' => $unique_filename,
            'filepath' => $upload_path,
            'url' => $upload_path
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

function deleteUploadedFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true; // File doesn't exist, consider it deleted
}

function validateImageFile($file) {
    // Additional image validation
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return false;
    }
    
    // Check if it's actually an image
    $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($image_info['mime'], $allowed_mime_types)) {
        return false;
    }
    
    return true;
}
?>
