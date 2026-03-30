<?php require_once '../../services/auth.php'; require_any_role(['admin', 'manager']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Operations</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
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
            <h1>Gallery Operations</h1>
        </div>

        <div class="callout" style="margin:16px 0;padding:12px 16px;border-left:4px solid #1f6feb;background:#eef5ff;">
            Gallery upload and delete tools now live directly on the public <strong>Gallery</strong> page for managers and admins.
            Blog post images also flow into the gallery automatically.
        </div>

        <p><a class="details-btn" href="../gallery.html">Open Gallery Workspace</a></p>
    </div>
</body>
</html>

