<?php
if (!session_status()) session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

try {
    // Pagination params
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(50, max(1, intval($_GET['pageSize']))) : 10;
    $offset = ($page - 1) * $pageSize;

    // Count total
    $countRes = $conn->query("SELECT COUNT(*) AS cnt FROM blogs");
    $total = 0; if ($countRes) { $row = $countRes->fetch_assoc(); $total = intval($row['cnt']); $countRes->close(); }

    $sql = "SELECT b.blog_id, b.blog_title, b.blog_content, b.created_at, u.user_name AS author
            FROM blogs b
            LEFT JOIN users u ON u.user_id = b.author_user_id
            ORDER BY b.blog_id DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $pageSize, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $blogs = [];
    while ($row = $result->fetch_assoc()) {
        $blogs[] = $row;
    }
    
    echo json_encode(['items' => $blogs, 'page' => $page, 'pageSize' => $pageSize, 'total' => $total]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch blogs: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 
