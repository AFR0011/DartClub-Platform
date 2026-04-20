<?php require_once '../../services/auth.php'; require_once '../../services/shared/admin_locale_helpers.php'; require_any_role(['admin', 'manager']); ?>
<!DOCTYPE html>
<html lang="<?php echo admin_html_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(admin_text('Blog Operations', 'Blog İşlemleri')); ?></title>
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
            <h1><?php echo htmlspecialchars(admin_text('Blog Operations', 'Blog İşlemleri')); ?></h1>
        </div>

        <div class="callout" style="margin:16px 0;padding:12px 16px;border-left:4px solid #1f6feb;background:#eef5ff;">
            <?php echo admin_text('Blog creation, moderation, comments, and reactions now live on the public <strong>Blog</strong> page with role-aware controls. Use that surface to create drafts, publish approved posts, and moderate community activity.', 'Blog oluşturma, moderasyon, yorumlar ve tepkiler artık role duyarlı kontrollerle genel <strong>Blog</strong> sayfasında yer alıyor. Taslak oluşturmak, onaylı gönderileri yayımlamak ve topluluk etkinliğini yönetmek için bu yüzeyi kullanın.'); ?>
        </div>

        <p><a class="details-btn" href="../blog.html"><?php echo htmlspecialchars(admin_text('Open Blog Workspace', 'Blog Çalışma Alanını Aç')); ?></a></p>
    </div>
</body>
</html>
