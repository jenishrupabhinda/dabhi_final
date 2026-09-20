<?php
/**
 * Image upload helper — lives in helpers/functions.php addendum.
 * Call uploadImage($_FILES['field'], 'subdirectory') and get:
 *   ['ok'=>true, 'path'=>'/assets/images/products/filename.jpg']
 * or ['ok'=>false, 'error'=>'...']
 */
if (!function_exists('uploadImage')) {
    function uploadImage(array $file, string $subdir = 'products'): array
    {
        if (!isset($file['tmp_name']) || empty($file['name'])) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload error code: ' . $file['error']];
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => 'Only JPEG, PNG, WebP, or GIF images are allowed.'];
        }

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'Image must be under 5 MB.'];
        }

        $ext     = $allowed[$mime];
        $dirReal = dirname(__DIR__, 2) . '/public_html/assets/images/' . $subdir;
        if (!is_dir($dirReal)) {
            mkdir($dirReal, 0755, true);
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $destPath = $dirReal . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Failed to save image. Check folder permissions.'];
        }

        return ['ok' => true, 'path' => '/assets/images/' . $subdir . '/' . $filename];
    }
}

if (!function_exists('csrfVerifyToken')) {
    function csrfVerifyToken(string $token): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }
}
