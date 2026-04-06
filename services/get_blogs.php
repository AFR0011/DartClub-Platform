<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

try {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(100, max(1, (int) $_GET['pageSize'])) : 10;
    $offset = ($page - 1) * $pageSize;
    $userId = get_current_user_id();

    $whereClause = "WHERE b.status = 'published'";
    if (can_publish_blog_posts()) {
        $whereClause = '';
    } elseif (is_logged_in()) {
        $whereClause = "WHERE b.status = 'published' OR b.author_user_id = " . (int) $userId;
    }

    $countSql = "SELECT COUNT(*) AS cnt FROM blogs b {$whereClause}";
    $countResult = $conn->query($countSql);
    $countRow = $countResult ? $countResult->fetch_assoc() : ['cnt' => 0];
    $total = (int) ($countRow['cnt'] ?? 0);
    if ($countResult) {
        $countResult->close();
    }

    $sql = "SELECT
                b.blog_id,
                b.blog_title,
                b.blog_content,
                b.status,
                b.created_at,
                b.updated_at,
                b.published_at,
                b.author_user_id,
                b.blog_category,
                b.blog_tags,
                u.user_name AS author,
                COALESCE(reactions.like_count, 0) AS like_count,
                COALESCE(comments.comment_count, 0) AS comment_count
            FROM blogs b
            LEFT JOIN users u ON u.user_id = b.author_user_id
            LEFT JOIN (
                SELECT blog_id, COUNT(*) AS like_count
                FROM blog_reactions
                WHERE reaction_type = 'like'
                GROUP BY blog_id
            ) reactions ON reactions.blog_id = b.blog_id
            LEFT JOIN (
                SELECT blog_id, COUNT(*) AS comment_count
                FROM blog_comments
                WHERE is_deleted = 0
                GROUP BY blog_id
            ) comments ON comments.blog_id = b.blog_id
            {$whereClause}
            ORDER BY COALESCE(b.published_at, b.created_at) DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $pageSize, $offset);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($items as &$item) {
        $blogId = (int) $item['blog_id'];

        $imagesStmt = $conn->prepare(
            'SELECT blog_image_id, file_path, title
             FROM blog_images
             WHERE blog_id = ?
             ORDER BY blog_image_id ASC'
        );
        $imagesStmt->bind_param('i', $blogId);
        $imagesStmt->execute();
        $rawImages = $imagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $imagesStmt->close();
        $item['images'] = [];
        foreach ($rawImages as $imageRow) {
            $imagePath = app_existing_public_path($imageRow['file_path'] ?? null);
            if ($imagePath === null) {
                continue;
            }

            $imageRow['file_path'] = $imagePath;
            $item['images'][] = $imageRow;
        }

        $commentsStmt = $conn->prepare(
            "SELECT
                c.comment_id,
                c.comment_content,
                c.created_at,
                c.updated_at,
                c.user_id,
                u.user_name
             FROM blog_comments c
             JOIN users u ON u.user_id = c.user_id
             WHERE c.blog_id = ? AND c.is_deleted = 0
             ORDER BY c.created_at ASC"
        );
        $commentsStmt->bind_param('i', $blogId);
        $commentsStmt->execute();
        $item['comments'] = $commentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $commentsStmt->close();

        if ($userId) {
            $likedStmt = $conn->prepare(
                "SELECT COUNT(*) AS liked
                 FROM blog_reactions
                 WHERE blog_id = ? AND user_id = ? AND reaction_type = 'like'"
            );
            $likedStmt->bind_param('ii', $blogId, $userId);
            $likedStmt->execute();
            $likedRow = $likedStmt->get_result()->fetch_assoc();
            $item['viewer_has_liked'] = ((int) ($likedRow['liked'] ?? 0)) > 0;
            $likedStmt->close();
        } else {
            $item['viewer_has_liked'] = false;
        }

        $item['blog_category'] = trim((string) ($item['blog_category'] ?? '')) ?: 'Announcement';
        $item['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($item['blog_tags'] ?? '')))));

        $item['viewer_can_delete'] = can_publish_blog_posts()
            || ($userId && (int) $item['author_user_id'] === (int) $userId && $item['status'] !== 'published');
        $item['viewer_can_publish'] = can_publish_blog_posts() && $item['status'] !== 'published';
    }
    unset($item);

    app_json_response([
        'items' => $items,
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
    ]);
} catch (Throwable $exception) {
    app_json_response(['error' => 'Failed to fetch blogs: ' . $exception->getMessage()], 500);
}
