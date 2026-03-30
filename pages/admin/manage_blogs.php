<?php require_once '../../services/auth.php'; require_any_role(['admin', 'manager']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Operations</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
</head>
<body>
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="../main.php">Back to Website</a>
    </div>
    <div class="container">
        <div class="header-actions">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
            <h1>Blog Operations</h1>
        </div>

        <div class="callout" style="margin:16px 0;padding:12px 16px;border-left:4px solid #1f6feb;background:#eef5ff;">
            Blog creation, moderation, comments, and reactions now live on the public <strong>Blog</strong> page with role-aware controls.
            Use that surface to create drafts, publish approved posts, and moderate community activity.
        </div>

        <p><a class="details-btn" href="../blog.html">Open Blog Workspace</a></p>
    </div>
</body>
</html>

