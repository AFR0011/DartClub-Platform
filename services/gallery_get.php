<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';

try {
    $stmt = $conn->prepare(
        'SELECT id, file_path, title, source_blog_id, uploaded_by_user_id, created_at
         FROM gallery_images
         ORDER BY created_at DESC'
    );
    $stmt->execute();
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($images)) {
        app_json_response($images);
    }

    $fallbackImages = [];
    $dir = __DIR__ . '/../files/media/images/gallery';
    if (is_dir($dir)) {
        $files = scandir($dir) ?: [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $index = 1;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            $fallbackImages[] = [
                'id' => -1 * $index,
                'file_path' => '../files/media/images/gallery/' . $file,
                'title' => pathinfo($file, PATHINFO_FILENAME),
                'source_blog_id' => null,
                'uploaded_by_user_id' => null,
                'created_at' => null,
            ];
            $index++;
        }
    }

    app_json_response($fallbackImages);
} catch (Throwable $exception) {
    app_json_response(['error' => 'Failed to fetch gallery images: ' . $exception->getMessage()], 500);
}

