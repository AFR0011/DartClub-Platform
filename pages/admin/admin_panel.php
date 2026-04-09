<?php
require_once '../../services/auth.php';
require_any_role(['admin', 'manager']);
require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/shared/tournament_helpers.php';

$tournamentIdStmt = $conn->prepare('SELECT tour_id FROM tournaments ORDER BY tour_creationDate DESC');
$tournamentIdStmt->execute();
$tournamentRows = $tournamentIdStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tournamentIdStmt->close();

$tournaments = [];
foreach ($tournamentRows as $row) {
    $tournaments[] = tournament_refresh_lifecycle($conn, (int) $row['tour_id']);
}

$statusCounts = [
    'registration_open' => 0,
    'registration_closed' => 0,
    'in_progress' => 0,
    'completed' => 0,
];

foreach ($tournaments as $tournament) {
    $status = (string) ($tournament['status'] ?? '');
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$pendingMemberships = (int) (($conn->query("SELECT COUNT(*) AS cnt FROM membership_applications WHERE status = 'Pending'")->fetch_assoc()['cnt'] ?? 0));
$pendingDrafts = (int) (($conn->query("SELECT COUNT(*) AS cnt FROM blogs WHERE status <> 'published'")->fetch_assoc()['cnt'] ?? 0));
$unscoredIndividualMatches = (int) (($conn->query("SELECT COUNT(*) AS cnt FROM matches m JOIN tournaments t ON t.tour_id = m.tour_id WHERE t.status = 'in_progress' AND m.match_status <> 'Completed'")->fetch_assoc()['cnt'] ?? 0));
$unscoredTeamMatches = (int) (($conn->query("SELECT COUNT(*) AS cnt FROM team_matches tm JOIN tournaments t ON t.tour_id = tm.tour_id WHERE t.status = 'in_progress' AND tm.match_status <> 'Completed'")->fetch_assoc()['cnt'] ?? 0));
$scoreBacklog = $unscoredIndividualMatches + $unscoredTeamMatches;

$pendingMembershipRows = $conn->query(
    "SELECT ma.application_id, ma.submitted_at, u.user_name, u.email
     FROM membership_applications ma
     JOIN users u ON u.user_id = ma.user_id
     WHERE ma.status = 'Pending'
     ORDER BY ma.submitted_at DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$attentionTournaments = array_values(array_filter($tournaments, static function (array $tournament): bool {
    return in_array((string) ($tournament['status'] ?? ''), ['registration_open', 'registration_closed', 'in_progress'], true);
}));
usort($attentionTournaments, static function (array $left, array $right): int {
    return strcmp((string) ($right['tour_creationDate'] ?? ''), (string) ($left['tour_creationDate'] ?? ''));
});
$attentionTournaments = array_slice($attentionTournaments, 0, 5);
?>
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
        .nav-card,
        .summary-card,
        .list-card {
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

        .mini-note {
            color: #5b6678;
        }

        .summary-grid,
        .panel-grid,
        .attention-grid {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }

        .summary-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .panel-grid,
        .attention-grid {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .summary-card strong {
            display: block;
            font-size: 1.7rem;
            color: #18212f;
            line-height: 1.05;
        }

        .summary-card span,
        .list-card p {
            color: #5b6678;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .list-card + .list-card {
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
            <p class="mini-note">Use the sidebar for full operations. This page is the quick triage view for membership, tournament readiness, content, and scoring backlog.</p>
            <div class="nav-actions">
                <a class="details-btn" href="manage_tournaments.php">Tournament Operations</a>
                <a class="details-btn" href="manage_players.php">Players & Membership</a>
                <a class="details-btn" href="manage_users.php">User Access</a>
                <a class="details-btn" href="../blog.html">Public Blog</a>
            </div>
        </div>

        <div class="summary-grid">
            <article class="summary-card">
                <span>Pending memberships</span>
                <strong><?php echo $pendingMemberships; ?></strong>
            </article>
            <article class="summary-card">
                <span>Pending drafts</span>
                <strong><?php echo $pendingDrafts; ?></strong>
            </article>
            <article class="summary-card">
                <span>Open registrations</span>
                <strong><?php echo $statusCounts['registration_open']; ?></strong>
            </article>
            <article class="summary-card">
                <span>Waiting to start</span>
                <strong><?php echo $statusCounts['registration_closed']; ?></strong>
            </article>
            <article class="summary-card">
                <span>Live tournaments</span>
                <strong><?php echo $statusCounts['in_progress']; ?></strong>
            </article>
            <article class="summary-card">
                <span>Unscored live matches</span>
                <strong><?php echo $scoreBacklog; ?></strong>
            </article>
        </div>

        <div class="attention-grid">
            <section class="panel-card">
                <h2 style="margin-top:0;">Membership Review Queue</h2>
                <p class="mini-note">Most recent pending applications that still need a decision.</p>
                <div style="margin-top:16px;">
                    <?php if (!$pendingMembershipRows): ?>
                        <div class="list-card">
                            <strong>No pending applications</strong>
                            <p>Membership review is currently clear.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingMembershipRows as $item): ?>
                            <div class="list-card">
                                <strong><?php echo htmlspecialchars((string) $item['user_name']); ?></strong>
                                <p><?php echo htmlspecialchars((string) $item['email']); ?></p>
                                <p>Submitted: <?php echo htmlspecialchars((string) $item['submitted_at']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a class="details-btn" href="manage_players.php" style="margin-top:14px; display:inline-flex;">Open membership review</a>
            </section>

            <section class="panel-card">
                <h2 style="margin-top:0;">Tournament Attention Queue</h2>
                <p class="mini-note">Registration, start, and live scoring work that is still active.</p>
                <div style="margin-top:16px;">
                    <?php if (!$attentionTournaments): ?>
                        <div class="list-card">
                            <strong>No active tournament queue</strong>
                            <p>There are no registration-open, waiting, or live tournaments right now.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($attentionTournaments as $tournament): ?>
                            <div class="list-card">
                                <strong><?php echo htmlspecialchars((string) $tournament['tour_title']); ?></strong>
                                <p><?php echo htmlspecialchars((string) $tournament['tour_type']); ?> - <?php echo htmlspecialchars((string) $tournament['status']); ?></p>
                                <a class="details-btn" href="show_tournament_details.php?id=<?php echo (int) $tournament['tour_id']; ?>" style="margin-top:10px; display:inline-flex;">Open workspace</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel-card">
                <h2 style="margin-top:0;">Public Surfaces</h2>
                <p class="mini-note">Jump directly into the public pages that members and guests actually use.</p>
                <div class="nav-actions">
                    <a class="details-btn" href="../main.php">Homepage</a>
                    <a class="details-btn" href="../tournaments.html">Tournaments</a>
                    <a class="details-btn" href="../blog.html">Blog</a>
                    <a class="details-btn" href="../gallery.html">Gallery</a>
                    <a class="details-btn" href="../register.html">Membership page</a>
                </div>
            </section>
        </div>
    </div>
</body>

</html>
