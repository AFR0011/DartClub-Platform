<?php
require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';

app_start_session();
require_any_role(['admin', 'manager']);

$playersQuery = $conn->query(
    "SELECT
        p.plr_idNum,
        p.plr_name,
        p.plr_surname,
        p.plr_phone,
        u.user_id,
        u.user_name,
        u.email,
        u.user_role,
        u.membership_status,
        u.member_since
     FROM players p
     LEFT JOIN users u ON u.user_id = p.user_id
     ORDER BY p.plr_surname, p.plr_name"
);
$players = $playersQuery ? $playersQuery->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Players & Membership</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .summary-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-bottom: 24px;
        }

        .summary-card,
        .panel-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(245, 248, 255, 0.92));
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 22px;
            padding: 18px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }

        .membership-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .application-status {
            font-weight: 700;
        }

        .application-status.pending { color: #b26a00; }
        .application-status.approved { color: #0b7f3f; }
        .application-status.rejected { color: #b42318; }

        .empty-state {
            padding: 16px;
            background: #f8fbff;
            border: 1px dashed #b6c7dd;
            border-radius: 16px;
        }

        iframe {
            width: 100%;
            min-height: 540px;
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 18px;
            background: #fff;
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
            <h1>Players & Membership</h1>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <strong>What happens here</strong>
                <p>Review club membership applications, approve or reject applicants, and inspect the current player/member roster.</p>
            </div>
            <div class="summary-card">
                <strong>Membership model</strong>
                <p>Club membership is separate from auth roles. A user can stay a <code>player</code> and still become an approved club member.</p>
            </div>
            <div class="summary-card">
                <strong>Authoring rules</strong>
                <p>Approved members can create blog drafts. Managers and admins can publish them.</p>
            </div>
        </div>

        <div class="panel-card">
            <h2>Membership Applications</h2>
            <p>Pending applications appear first. Use the buttons to review and change the applicant's membership state.</p>
            <div id="membership-applications" class="empty-state">Loading applications...</div>
        </div>

        <div class="panel-card" style="margin-top: 24px;">
            <h2>Application Preview</h2>
            <p>Select an application to preview the uploaded form.</p>
            <iframe id="application-preview" title="Membership application preview"></iframe>
        </div>

        <div class="panel-card" style="margin-top: 24px;">
            <h2>Player Registry</h2>
            <table class="players-table">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Membership</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $player): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(trim(($player['plr_name'] ?? '') . ' ' . ($player['plr_surname'] ?? ''))); ?></td>
                            <td><?php echo htmlspecialchars((string) ($player['user_name'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($player['email'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($player['plr_phone'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($player['user_role'] ?? 'guest')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($player['membership_status'] ?? 'not_submitted')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const previewFrame = document.getElementById('application-preview');

        function renderApplicationRow(item) {
            const statusClass = (item.status || '').toLowerCase();
            return `
                <tr>
                    <td>${item.application_id}</td>
                    <td>${item.user_name}</td>
                    <td>${item.email}</td>
                    <td>${item.plr_name ? `${item.plr_name} ${item.plr_surname}` : 'No player profile yet'}</td>
                    <td><span class="application-status ${statusClass}">${item.status}</span></td>
                    <td>${item.submitted_at || '-'}</td>
                    <td>${item.reviewed_at || '-'}</td>
                    <td class="membership-actions">
                        <button type="button" onclick="previewApplication('${item.application_file_path}')">View</button>
                        <button type="button" onclick="reviewApplication(${item.application_id}, 'approve')">Approve</button>
                        <button type="button" class="cancel-btn" onclick="reviewApplication(${item.application_id}, 'reject')">Reject</button>
                    </td>
                </tr>
            `;
        }

        async function loadApplications() {
            const container = document.getElementById('membership-applications');
            try {
                const response = await fetch('../../services/get_membership_applications.php');
                const data = await response.json();
                if (!data.success || !Array.isArray(data.items)) {
                    throw new Error(data.message || 'Failed to load membership applications.');
                }

                if (data.items.length === 0) {
                    container.innerHTML = '<div class="empty-state">No membership applications have been submitted yet.</div>';
                    return;
                }

                container.innerHTML = `
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Player Profile</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Reviewed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.items.map(renderApplicationRow).join('')}
                        </tbody>
                    </table>
                `;
            } catch (error) {
                container.innerHTML = `<div class="empty-state">${error.message}</div>`;
            }
        }

        function previewApplication(path) {
            previewFrame.src = path;
            previewFrame.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        async function reviewApplication(applicationId, decision) {
            const notes = window.prompt(
                decision === 'approve'
                    ? 'Optional approval note:'
                    : 'Add a rejection reason or note:',
                ''
            );

            const response = await fetch('../../services/review_membership_application.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: applicationId,
                    decision,
                    reviewer_notes: notes || ''
                })
            });
            const data = await response.json();
            if (!data.success) {
                window.alert(data.message || 'Review failed.');
                return;
            }

            await loadApplications();
            window.alert(`Application ${decision === 'approve' ? 'approved' : 'rejected'} successfully.`);
        }

        document.addEventListener('DOMContentLoaded', loadApplications);
    </script>
</body>
</html>
