<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';

try {
    $stmt = $conn->prepare(
        'SELECT
            g.id,
            g.file_path,
            g.title,
            g.source_blog_id,
            g.uploaded_by_user_id,
            g.created_at,
            b.blog_title AS source_blog_title
         FROM gallery_images g
         LEFT JOIN blogs b ON b.blog_id = g.source_blog_id
         ORDER BY g.created_at DESC'
    );
    $stmt->execute();
    $rawImages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $images = [];
    foreach ($rawImages as $image) {
        $imagePath = app_existing_public_path($image['file_path'] ?? null);
        if ($imagePath === null) {
            continue;
        }

        $image['file_path'] = $imagePath;
        $images[] = $image;
    }

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
                'file_path' => app_public_path('../files/media/images/gallery/' . $file),
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
    app_json_response(['error' => 'Failed to fetch gallery images: ' . app_safe_error_message($exception)], 500);
}
