<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';

header('Content-Type: application/json');

try {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        title VARCHAR(255) DEFAULT NULL,
        uploaded_by_user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $stmt = $conn->prepare("SELECT id, file_path, title, uploaded_by_user_id, created_at FROM gallery_images ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }

    // If empty, import existing files from gallery folder once
    if (count($images) === 0) {
        $dir = __DIR__ . '/../files/media/images/gallery';
        if (is_dir($dir)) {
            $files = scandir($dir);
            $allowedExt = ['jpg','jpeg','png','gif','webp'];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) continue;
                $webPath = '../files/media/images/gallery/' . $f;
                $ins = $conn->prepare("INSERT INTO gallery_images (file_path, title) VALUES (?, ?)");
                $title = null;
                $ins->bind_param('ss', $webPath, $title);
                $ins->execute();
                $ins->close();
            }
            // Re-query after import
            $stmt2 = $conn->prepare("SELECT id, file_path, title, uploaded_by_user_id, created_at FROM gallery_images ORDER BY created_at DESC");
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $images = [];
            while ($row2 = $res2->fetch_assoc()) {
                $images[] = $row2;
            }
            $stmt2->close();
        }
    }

    echo json_encode($images);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch gallery images: ' . $e->getMessage()]);
}

$conn->close();