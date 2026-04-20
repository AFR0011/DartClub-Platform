<?php
require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';
require_once '../../services/shared/admin_locale_helpers.php';

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
<html lang="<?php echo admin_html_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(admin_text('Manage Players & Membership', 'Oyuncular ve Üyelik')); ?></title>
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
                width: 100%;
            }

            .membership-actions {
                justify-content: flex-start;
                min-width: 170px;
            }

            .action-menu summary {
                width: 100%;
                text-align: center;
            }

            .action-menu-panel {
                position: static;
                right: auto;
                top: auto;
                min-width: 0;
                margin-top: 8px;
                box-shadow: none;
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
            <h1><?php echo htmlspecialchars(admin_text('Players & Membership', 'Oyuncular ve Üyelik')); ?></h1>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <strong><?php echo htmlspecialchars(admin_text('What happens here', 'Burada ne yapılır')); ?></strong>
                <p><?php echo htmlspecialchars(admin_text('Review club membership applications, approve or reject applicants, and inspect the current player/member roster.', 'Kulüp üyelik başvurularını inceleyin, başvuranları onaylayın veya reddedin ve mevcut oyuncu/üye kadrosunu gözden geçirin.')); ?></p>
            </div>
            <div class="summary-card">
                <strong><?php echo htmlspecialchars(admin_text('Membership model', 'Üyelik modeli')); ?></strong>
                <p><?php echo admin_text('Club membership is separate from auth roles. A user can stay a <code>player</code> and still become an approved club member.', 'Kulüp üyeliği yetki rollerinden ayrıdır. Bir kullanıcı <code>oyuncu</code> olarak kalıp yine de onaylı kulüp üyesi olabilir.'); ?></p>
            </div>
            <div class="summary-card">
                <strong><?php echo htmlspecialchars(admin_text('Authoring rules', 'İçerik kuralları')); ?></strong>
                <p><?php echo htmlspecialchars(admin_text('Approved members can create blog drafts. Managers and admins can publish them.', 'Onaylı üyeler blog taslakları oluşturabilir. Yöneticiler ve adminler bunları yayımlayabilir.')); ?></p>
            </div>
        </div>

        <div class="section-toggle">
            <button type="button" class="active" data-section-button="membership_applications"><?php echo htmlspecialchars(admin_text('Membership Applications', 'Üyelik Başvuruları')); ?></button>
            <button type="button" data-section-button="player_registry"><?php echo htmlspecialchars(admin_text('Player Registry', 'Oyuncu Kaydı')); ?></button>
        </div>

        <section class="page-section active" data-section="membership_applications">
        <div class="panel-card">
            <h2><?php echo htmlspecialchars(admin_text('Membership Applications', 'Üyelik Başvuruları')); ?></h2>
            <p><?php echo htmlspecialchars(admin_text("Pending applications appear first. Use the buttons to review and change the applicant's membership state.", 'Bekleyen başvurular önce görünür. Başvuranın üyelik durumunu incelemek ve değiştirmek için düğmeleri kullanın.')); ?></p>
            <div class="toolbar-line">
                <div>
                    <label for="applicationFilter"><?php echo htmlspecialchars(admin_text('Filter applications', 'Başvuruları filtrele')); ?></label>
                    <input type="text" id="applicationFilter" placeholder="<?php echo htmlspecialchars(admin_text('Search by user, email, or player name', 'Kullanıcı, e-posta veya oyuncu adına göre ara')); ?>">
                </div>
                <div>
                    <label for="applicationStatusFilter"><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></label>
                    <select id="applicationStatusFilter">
                        <option value="all"><?php echo htmlspecialchars(admin_text('All statuses', 'Tüm durumlar')); ?></option>
                        <option value="pending"><?php echo htmlspecialchars(admin_membership_label('pending')); ?></option>
                        <option value="approved"><?php echo htmlspecialchars(admin_membership_label('approved')); ?></option>
                        <option value="rejected"><?php echo htmlspecialchars(admin_membership_label('rejected')); ?></option>
                    </select>
                </div>
            </div>
            <div id="membership-applications" class="empty-state"><?php echo htmlspecialchars(admin_text('Loading applications...', 'Başvurular yükleniyor...')); ?></div>
        </div>

        <div class="panel-card" style="margin-top: 24px;">
            <h2><?php echo htmlspecialchars(admin_text('Application Preview', 'Başvuru Önizlemesi')); ?></h2>
            <p><?php echo htmlspecialchars(admin_text('Select an application to preview the uploaded form.', 'Yüklenen formu önizlemek için bir başvuru seçin.')); ?></p>
            <p class="helper-note"><?php echo htmlspecialchars(admin_text('PDF files preview inline. DOC and DOCX files open in a new tab or download, depending on the browser.', 'PDF dosyaları sayfa içinde önizlenir. DOC ve DOCX dosyaları ise tarayıcıya bağlı olarak yeni sekmede açılır veya indirilir.')); ?></p>
            <iframe id="application-preview" title="<?php echo htmlspecialchars(admin_text('Membership application preview', 'Üyelik başvurusu önizlemesi')); ?>"></iframe>
        </div>
        </section>

        <section class="page-section" data-section="player_registry">
        <div class="panel-card" style="margin-top: 24px;">
            <h2><?php echo htmlspecialchars(admin_text('Player Registry', 'Oyuncu Kaydı')); ?></h2>
            <div class="toolbar-line">
                <div>
                    <label for="registryFilter"><?php echo htmlspecialchars(admin_text('Filter registry', 'Kaydı filtrele')); ?></label>
                    <input type="text" id="registryFilter" placeholder="<?php echo htmlspecialchars(admin_text('Search by player, username, or email', 'Oyuncu, kullanıcı adı veya e-postaya göre ara')); ?>">
                </div>
                <div>
                    <label for="registrySort"><?php echo htmlspecialchars(admin_text('Sort registry', 'Kaydı sırala')); ?></label>
                    <select id="registrySort">
                        <option value="name_asc"><?php echo htmlspecialchars(admin_text('Player A-Z', 'Oyuncu A-Z')); ?></option>
                        <option value="name_desc"><?php echo htmlspecialchars(admin_text('Player Z-A', 'Oyuncu Z-A')); ?></option>
                        <option value="role"><?php echo htmlspecialchars(admin_text('Role', 'Rol')); ?></option>
                        <option value="membership"><?php echo htmlspecialchars(admin_text('Membership', 'Üyelik')); ?></option>
                    </select>
                </div>
            </div>
            <div class="table-shell">
                <table class="players-table" id="playerRegistryTable">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(admin_text('Player', 'Oyuncu')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Username', 'Kullanıcı Adı')); ?></th>
                            <th>Email</th>
                            <th><?php echo htmlspecialchars(admin_text('Phone', 'Telefon')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Role', 'Rol')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Membership', 'Üyelik')); ?></th>
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
                                <td><?php echo htmlspecialchars(admin_role_label((string) ($player['user_role'] ?? 'player'))); ?></td>
                                <td><?php echo htmlspecialchars(admin_membership_label((string) ($player['membership_status'] ?? 'not_submitted'))); ?></td>
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
        const playersCopy = <?php echo json_encode([
            'noPlayerProfileYet' => admin_text('No player profile yet', 'Henüz oyuncu profili yok'),
            'view' => admin_text('View', 'Görüntüle'),
            'approve' => admin_text('Approve', 'Onayla'),
            'reject' => admin_text('Reject', 'Reddet'),
            'actions' => admin_text('Actions', 'İşlemler'),
            'noApplicationsMatch' => admin_text('No membership applications match the current filters.', 'Geçerli filtrelerle eşleşen üyelik başvurusu yok.'),
            'tableUser' => admin_text('User', 'Kullanıcı'),
            'tablePlayerProfile' => admin_text('Player Profile', 'Oyuncu Profili'),
            'tableStatus' => admin_text('Status', 'Durum'),
            'tableSubmitted' => admin_text('Submitted', 'Gönderildi'),
            'tableReviewed' => admin_text('Reviewed', 'İncelendi'),
            'tableActions' => admin_text('Actions', 'İşlemler'),
            'loadApplicationsFailed' => admin_text('Failed to load membership applications.', 'Üyelik başvuruları yüklenemedi.'),
            'noApplicationsYet' => admin_text('No membership applications have been submitted yet.', 'Henüz üyelik başvurusu gönderilmedi.'),
            'noApplicationFile' => admin_text('No application file is available for this record.', 'Bu kayıt için kullanılabilir bir başvuru dosyası yok.'),
            'docPreviewUnavailable' => admin_text('Document preview unavailable', 'Belge önizlemesi kullanılamıyor'),
            'openApplicationFile' => admin_text('Open application file', 'Başvuru dosyasını aç'),
            'approveTitle' => admin_text('Approve membership application', 'Üyelik başvurusunu onayla'),
            'rejectTitle' => admin_text('Reject membership application', 'Üyelik başvurusunu reddet'),
            'approveMessage' => admin_text('Add an optional note for this approval.', 'Bu onay için isteğe bağlı bir not ekleyin.'),
            'rejectMessage' => admin_text('Add a rejection reason or note so the decision is easier to audit later.', 'Kararın daha sonra daha kolay denetlenebilmesi için bir ret gerekçesi veya not ekleyin.'),
            'approvePlaceholder' => admin_text('Optional approval note', 'İsteğe bağlı onay notu'),
            'rejectPlaceholder' => admin_text('Reason or review note', 'Gerekçe veya inceleme notu'),
            'approveConfirm' => admin_text('Approve application', 'Başvuruyu onayla'),
            'rejectConfirm' => admin_text('Reject application', 'Başvuruyu reddet'),
            'cancel' => admin_text('Cancel', 'İptal'),
            'reviewFailed' => admin_text('Review failed.', 'İnceleme başarısız oldu.'),
            'applicationApproved' => admin_text('Application approved successfully.', 'Başvuru başarıyla onaylandı.'),
            'applicationRejected' => admin_text('Application rejected successfully.', 'Başvuru başarıyla reddedildi.'),
            'submittedLabel' => admin_text('Submitted', 'Gönderildi'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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
            const playerProfile = item.plr_name ? `${item.plr_name} ${item.plr_surname}` : playersCopy.noPlayerProfileYet;
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
                            <button type="button" class="action-btn" onclick='previewApplication(${filePath}, ${originalFilename})'>${escapeHtml(playersCopy.view)}</button>
                            <button type="button" class="submit-btn" onclick="reviewApplication(${applicationId}, 'approve')">${escapeHtml(playersCopy.approve)}</button>
                            <button type="button" class="cancel-btn" onclick="reviewApplication(${applicationId}, 'reject')">${escapeHtml(playersCopy.reject)}</button>
                        </div>
                        <details class="action-menu">
                            <summary>${escapeHtml(playersCopy.actions)}</summary>
                            <div class="action-menu-panel">
                                <button type="button" class="action-btn" onclick='previewApplication(${filePath}, ${originalFilename})'>${escapeHtml(playersCopy.view)}</button>
                                <button type="button" class="submit-btn" onclick="reviewApplication(${applicationId}, 'approve')">${escapeHtml(playersCopy.approve)}</button>
                                <button type="button" class="cancel-btn" onclick="reviewApplication(${applicationId}, 'reject')">${escapeHtml(playersCopy.reject)}</button>
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
                container.innerHTML = `<div class="empty-state">${escapeHtml(playersCopy.noApplicationsMatch)}</div>`;
                return;
            }

            container.innerHTML = `
                <div class="table-shell">
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>${escapeHtml(playersCopy.tableUser)}</th>
                                <th>Email</th>
                                <th>${escapeHtml(playersCopy.tablePlayerProfile)}</th>
                                <th>${escapeHtml(playersCopy.tableStatus)}</th>
                                <th>${escapeHtml(playersCopy.tableSubmitted)}</th>
                                <th>${escapeHtml(playersCopy.tableReviewed)}</th>
                                <th>${escapeHtml(playersCopy.tableActions)}</th>
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
                    throw new Error(data.message || playersCopy.loadApplicationsFailed);
                }

                membershipApplications = data.items;
                if (membershipApplications.length === 0) {
                    container.innerHTML = `<div class="empty-state">${escapeHtml(playersCopy.noApplicationsYet)}</div>`;
                    return;
                }

                renderApplications();
            } catch (error) {
                container.innerHTML = `<div class="empty-state">${error.message}</div>`;
            }
        }

        function previewApplication(path, originalFilename = '') {
            if (!path) {
                playersToast(playersCopy.noApplicationFile, 'warning');
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
                        <h3 style="margin-top: 0;">${escapeHtml(playersCopy.docPreviewUnavailable)}</h3>
                        <p>${safeFilename} ${window.getAdminLocale?.() === 'tr' ? 'tarayıcınıza bağlı olarak yeni sekmede açılır veya indirilir.' : 'will open in a new tab or download, depending on your browser.'}</p>
                        <p><a href="${normalizedPath}" target="_blank" rel="noopener">${escapeHtml(playersCopy.openApplicationFile)}</a></p>
                    </div>
                `;
                window.open(normalizedPath, '_blank', 'noopener');
            }

            previewFrame.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        async function reviewApplication(applicationId, decision) {
            const notes = await playersPrompt({
                title: decision === 'approve' ? playersCopy.approveTitle : playersCopy.rejectTitle,
                message: decision === 'approve'
                    ? playersCopy.approveMessage
                    : playersCopy.rejectMessage,
                initialValue: '',
                placeholder: decision === 'approve' ? playersCopy.approvePlaceholder : playersCopy.rejectPlaceholder,
                multiline: true,
                confirmLabel: decision === 'approve' ? playersCopy.approveConfirm : playersCopy.rejectConfirm,
                cancelLabel: playersCopy.cancel,
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
                    throw new Error(data.message || playersCopy.reviewFailed);
                }

                await loadApplications();
                playersToast(decision === 'approve' ? playersCopy.applicationApproved : playersCopy.applicationRejected, 'success');
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
