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
            justify-content: flex-end;
        }

        .membership-actions-inline {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }

        .toolbar-line {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px);
            align-items: end;
            margin: 16px 0;
        }

        .table-shell {
            overflow: auto;
            border-radius: 22px;
        }

        .action-menu {
            position: relative;
            display: none;
        }

        .action-menu summary {
            list-style: none;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            color: #fff;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }

        .action-menu summary::-webkit-details-marker {
            display: none;
        }

        .action-menu-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            z-index: 6;
            min-width: 170px;
            display: grid;
            gap: 8px;
            padding: 12px;
            border-radius: 18px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        .action-menu-panel button {
            width: 100%;
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

        .helper-note {
            color: #5b6678;
            margin-top: 12px;
        }

        @media (max-width: 980px) {
            .membership-actions-inline {
                display: none;
            }

            .action-menu {
                display: block;
            }
        }

        @media (max-width: 760px) {
            .toolbar-line {
                grid-template-columns: 1fr;
            }
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

        <div class="section-toggle">
            <button type="button" class="active" data-section-button="membership_applications">Membership Applications</button>
            <button type="button" data-section-button="player_registry">Player Registry</button>
        </div>

        <section class="page-section active" data-section="membership_applications">
        <div class="panel-card">
            <h2>Membership Applications</h2>
            <p>Pending applications appear first. Use the buttons to review and change the applicant's membership state.</p>
            <div class="toolbar-line">
                <div>
                    <label for="applicationFilter">Filter applications</label>
                    <input type="text" id="applicationFilter" placeholder="Search by user, email, or player name">
                </div>
                <div>
                    <label for="applicationStatusFilter">Status</label>
                    <select id="applicationStatusFilter">
                        <option value="all">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div id="membership-applications" class="empty-state">Loading applications...</div>
        </div>

        <div class="panel-card" style="margin-top: 24px;">
            <h2>Application Preview</h2>
            <p>Select an application to preview the uploaded form.</p>
            <p class="helper-note">PDF files preview inline. DOC and DOCX files open in a new tab or download, depending on the browser.</p>
            <iframe id="application-preview" title="Membership application preview"></iframe>
        </div>
        </section>

        <section class="page-section" data-section="player_registry">
        <div class="panel-card" style="margin-top: 24px;">
            <h2>Player Registry</h2>
            <div class="toolbar-line">
                <div>
                    <label for="registryFilter">Filter registry</label>
                    <input type="text" id="registryFilter" placeholder="Search by player, username, or email">
                </div>
                <div>
                    <label for="registrySort">Sort registry</label>
                    <select id="registrySort">
                        <option value="name_asc">Player A-Z</option>
                        <option value="name_desc">Player Z-A</option>
                        <option value="role">Role</option>
                        <option value="membership">Membership</option>
                    </select>
                </div>
            </div>
            <div class="table-shell">
                <table class="players-table" id="playerRegistryTable">
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
                            <?php $playerName = trim(($player['plr_name'] ?? '') . ' ' . ($player['plr_surname'] ?? '')); ?>
                            <tr
                                data-player-registry-row
                                data-player-name="<?php echo htmlspecialchars(strtolower($playerName)); ?>"
                                data-player-role="<?php echo htmlspecialchars(strtolower((string) ($player['user_role'] ?? 'guest'))); ?>"
                                data-player-membership="<?php echo htmlspecialchars(strtolower((string) ($player['membership_status'] ?? 'not_submitted'))); ?>"
                                data-player-search="<?php echo htmlspecialchars(strtolower($playerName . ' ' . (string) ($player['user_name'] ?? '') . ' ' . (string) ($player['email'] ?? ''))); ?>"
                            >
                                <td><?php echo htmlspecialchars($playerName); ?></td>
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
        </section>
    </div>

    <script src="../../js/ui_feedback.js?v=20260406-1"></script>
    <script>
        const previewFrame = document.getElementById('application-preview');
        let membershipApplications = [];

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function normalizeText(value) {
            return String(value ?? '').trim().toLowerCase();
        }

        function playersToast(message, tone = 'info') {
            if (window.AppUI?.toast) {
                window.AppUI.toast(message, tone);
                return;
            }

            if (tone === 'error' || tone === 'warning') {
                window.alert(message);
                return;
            }

            console.info(message);
        }

        async function playersPrompt(config) {
            if (window.AppUI?.prompt) {
                return window.AppUI.prompt(config);
            }

            return window.prompt(config?.message || config?.title || '', config?.initialValue || '');
        }

        async function playersFetchJson(url, options = {}) {
            if (window.appFetchJson) {
                return window.appFetchJson(url, options);
            }

            const response = await fetch(url, options);
            return response.json();
        }

        function showSection(sectionName) {
            document.querySelectorAll('[data-section]').forEach((section) => {
                section.classList.toggle('active', section.dataset.section === sectionName);
            });

            document.querySelectorAll('[data-section-button]').forEach((button) => {
                button.classList.toggle('active', button.dataset.sectionButton === sectionName);
            });
        }

        function renderApplicationRow(item) {
            const statusClass = (item.status || '').toLowerCase();
            const filePath = JSON.stringify(item.application_file_path || '');
            const originalFilename = JSON.stringify(item.original_filename || '');
            const applicationId = Number(item.application_id || 0);
            const playerProfile = item.plr_name ? `${item.plr_name} ${item.plr_surname}` : 'No player profile yet';
            return `
                <tr>
                    <td>${applicationId}</td>
                    <td>${escapeHtml(item.user_name)}</td>
                    <td>${escapeHtml(item.email)}</td>
                    <td>${escapeHtml(playerProfile)}</td>
                    <td><span class="application-status ${escapeHtml(statusClass)}">${escapeHtml(item.status)}</span></td>
                    <td>${escapeHtml(item.submitted_at || '-')}</td>
                    <td>${escapeHtml(item.reviewed_at || '-')}</td>
                    <td class="membership-actions">
                        <div class="membership-actions-inline">
                            <button type="button" class="action-btn" onclick='previewApplication(${filePath}, ${originalFilename})'>View</button>
                            <button type="button" class="submit-btn" onclick="reviewApplication(${applicationId}, 'approve')">Approve</button>
                            <button type="button" class="cancel-btn" onclick="reviewApplication(${applicationId}, 'reject')">Reject</button>
                        </div>
                        <details class="action-menu">
                            <summary>Actions</summary>
                            <div class="action-menu-panel">
                                <button type="button" class="action-btn" onclick='previewApplication(${filePath}, ${originalFilename})'>View</button>
                                <button type="button" class="submit-btn" onclick="reviewApplication(${applicationId}, 'approve')">Approve</button>
                                <button type="button" class="cancel-btn" onclick="reviewApplication(${applicationId}, 'reject')">Reject</button>
                            </div>
                        </details>
                    </td>
                </tr>
            `;
        }

        function renderApplications() {
            const container = document.getElementById('membership-applications');
            const filterValue = normalizeText(document.getElementById('applicationFilter')?.value);
            const statusValue = normalizeText(document.getElementById('applicationStatusFilter')?.value || 'all');
            const items = membershipApplications.filter((item) => {
                const searchable = normalizeText([
                    item.user_name,
                    item.email,
                    item.plr_name,
                    item.plr_surname
                ].join(' '));
                const statusMatches = statusValue === 'all' || normalizeText(item.status) === statusValue;
                return statusMatches && searchable.includes(filterValue);
            });

            if (items.length === 0) {
                container.innerHTML = '<div class="empty-state">No membership applications match the current filters.</div>';
                return;
            }

            container.innerHTML = `
                <div class="table-shell">
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
                            ${items.map(renderApplicationRow).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        async function loadApplications() {
            const container = document.getElementById('membership-applications');
            try {
                const data = await playersFetchJson('../../services/get_membership_applications.php');
                if (!data.success || !Array.isArray(data.items)) {
                    throw new Error(data.message || 'Failed to load membership applications.');
                }

                membershipApplications = data.items;
                if (membershipApplications.length === 0) {
                    container.innerHTML = '<div class="empty-state">No membership applications have been submitted yet.</div>';
                    return;
                }

                renderApplications();
            } catch (error) {
                container.innerHTML = `<div class="empty-state">${error.message}</div>`;
            }
        }

        function previewApplication(path, originalFilename = '') {
            if (!path) {
                playersToast('No application file is available for this record.', 'warning');
                return;
            }

            const normalizedPath = path.startsWith('/') ? path : `/${path.replace(/^(\.\.\/)+/, '')}`;
            const lowerPath = normalizedPath.toLowerCase();

            if (lowerPath.endsWith('.pdf')) {
                previewFrame.removeAttribute('srcdoc');
                previewFrame.src = normalizedPath;
            } else {
                const safeFilename = escapeHtml(originalFilename || 'This file');
                previewFrame.removeAttribute('src');
                previewFrame.srcdoc = `
                    <div style="padding: 24px; font-family: Segoe UI, sans-serif; color: #18212f;">
                        <h3 style="margin-top: 0;">Document preview unavailable</h3>
                        <p>${safeFilename} will open in a new tab or download, depending on your browser.</p>
                        <p><a href="${normalizedPath}" target="_blank" rel="noopener">Open application file</a></p>
                    </div>
                `;
                window.open(normalizedPath, '_blank', 'noopener');
            }

            previewFrame.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        async function reviewApplication(applicationId, decision) {
            const notes = await playersPrompt({
                title: decision === 'approve' ? 'Approve membership application' : 'Reject membership application',
                message: decision === 'approve'
                    ? 'Add an optional note for this approval.'
                    : 'Add a rejection reason or note so the decision is easier to audit later.',
                initialValue: '',
                placeholder: decision === 'approve' ? 'Optional approval note' : 'Reason or review note',
                multiline: true,
                confirmLabel: decision === 'approve' ? 'Approve application' : 'Reject application',
                cancelLabel: 'Cancel',
                tone: decision === 'approve' ? 'primary' : 'danger'
            });
            if (notes === null) {
                return;
            }

            try {
                const data = await playersFetchJson('../../services/review_membership_application.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        application_id: applicationId,
                        decision,
                        reviewer_notes: notes || ''
                    })
                });
                if (!data.success) {
                    throw new Error(data.message || 'Review failed.');
                }

                await loadApplications();
                playersToast(`Application ${decision === 'approve' ? 'approved' : 'rejected'} successfully.`, 'success');
            } catch (error) {
                playersToast(error.message, 'error');
            }
        }

        function renderPlayerRegistry() {
            const tbody = document.querySelector('#playerRegistryTable tbody');
            if (!tbody) {
                return;
            }

            const filterValue = normalizeText(document.getElementById('registryFilter')?.value);
            const sortValue = document.getElementById('registrySort')?.value || 'name_asc';
            const rows = Array.from(tbody.querySelectorAll('[data-player-registry-row]'));

            rows.sort((left, right) => {
                const leftName = left.dataset.playerName || '';
                const rightName = right.dataset.playerName || '';
                if (sortValue === 'name_desc') {
                    return rightName.localeCompare(leftName, undefined, { sensitivity: 'base' });
                }
                if (sortValue === 'role') {
                    return (left.dataset.playerRole || '').localeCompare(right.dataset.playerRole || '', undefined, { sensitivity: 'base' })
                        || leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
                }
                if (sortValue === 'membership') {
                    return (left.dataset.playerMembership || '').localeCompare(right.dataset.playerMembership || '', undefined, { sensitivity: 'base' })
                        || leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
                }

                return leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
            });

            rows.forEach((row) => {
                const matches = (row.dataset.playerSearch || '').includes(filterValue);
                row.style.display = matches ? '' : 'none';
                tbody.appendChild(row);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-section-button]').forEach((button) => {
                button.addEventListener('click', () => showSection(button.dataset.sectionButton));
            });
            document.getElementById('applicationFilter')?.addEventListener('input', renderApplications);
            document.getElementById('applicationStatusFilter')?.addEventListener('change', renderApplications);
            document.getElementById('registryFilter')?.addEventListener('input', renderPlayerRegistry);
            document.getElementById('registrySort')?.addEventListener('change', renderPlayerRegistry);
            showSection('membership_applications');
            renderPlayerRegistry();
            loadApplications();
        });
    </script>
</body>
</html>
