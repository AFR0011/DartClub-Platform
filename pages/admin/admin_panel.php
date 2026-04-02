<?php require_once '../../services/auth.php'; require_any_role(['admin','manager']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dart Tournament Club Management</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .hero-card,
        .panel-card,
        .nav-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(245, 248, 255, 0.92));
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }

        .hero-card {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.14), transparent 32%),
                radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.12), transparent 32%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(244, 248, 255, 0.95));
        }

        .panel-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 24px;
        }

        .mini-note {
            color: #5b6678;
        }

        .nav-card a {
            display: inline-flex;
            margin-top: 12px;
        }
    </style>
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
            <h1>Dart Tournament Club Management</h1>
        </div>
        <div class="hero-card">
            <p class="mini-note">Use the sidebar for core admin workflows, or jump directly into the public-facing workspaces below.</p>
        </div>
        <div class="panel-grid">
            <div class="nav-card">
                <h2 style="margin-top:0;">Tournament Operations</h2>
                <p class="mini-note">Create events, manage rosters, and work directly inside the new connected bracket flow.</p>
                <a class="details-btn" href="manage_tournaments.php">Open tournaments</a>
            </div>
            <div class="nav-card">
                <h2 style="margin-top:0;">Players & Membership</h2>
                <p class="mini-note">Review membership applications and inspect the linked player/member roster.</p>
                <a class="details-btn" href="manage_players.php">Open player operations</a>
            </div>
            <div class="nav-card">
                <h2 style="margin-top:0;">Public Workspaces</h2>
                <p class="mini-note">Jump into the blog and gallery surfaces without leaving the admin console context.</p>
                <a class="details-btn" href="../blog.html">Open blog workspace</a>
                <a class="details-btn" href="../gallery.html" style="margin-left:8px;">Open gallery workspace</a>
            </div>
        </div>
    </div>
</body>

</html>
