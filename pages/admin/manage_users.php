<?php
require_once '../../services/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .hero-band {
            display: grid;
            gap: 18px;
            grid-template-columns: 1.3fr 1fr;
            margin-bottom: 22px;
        }

        .hero-card,
        .surface-panel,
        .summary-card {
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

        .summary-grid {
            display: grid;
            gap: 12px;
        }

        .summary-card strong {
            display: block;
            font-size: 1.35rem;
        }

        .mini-note {
            color: #5b6678;
        }

        .table-container {
            overflow: auto;
            border-radius: 22px;
        }

        .toolbar-line {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px);
            align-items: end;
            margin-top: 16px;
        }

        .status-stack {
            display: grid;
            gap: 6px;
        }

        .status-note {
            color: #5b6678;
            font-size: 0.82rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-pill.role-player {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }

        .status-pill.role-manager {
            background: rgba(245, 158, 11, 0.14);
            color: #b45309;
        }

        .status-pill.role-admin {
            background: rgba(124, 58, 237, 0.14);
            color: #6d28d9;
        }

        .status-pill.membership-approved {
            background: rgba(22, 163, 74, 0.12);
            color: #15803d;
        }

        .status-pill.membership-pending {
            background: rgba(245, 158, 11, 0.14);
            color: #b45309;
        }

        .status-pill.membership-rejected {
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
        }

        .status-pill.membership-not_submitted {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
        }

        .role-pill-select {
            width: auto;
            min-width: 132px;
            margin-bottom: 0;
            padding: 8px 34px 8px 12px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: capitalize;
            appearance: none;
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 10px 10px;
            background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%);
        }

        .role-pill-select.role-player {
            background-color: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            border-color: rgba(37, 99, 235, 0.18);
        }

        .role-pill-select.role-manager {
            background-color: rgba(245, 158, 11, 0.14);
            color: #b45309;
            border-color: rgba(245, 158, 11, 0.2);
        }

        .role-pill-select.role-admin {
            background-color: rgba(124, 58, 237, 0.14);
            color: #6d28d9;
            border-color: rgba(124, 58, 237, 0.2);
        }

        @media (max-width: 900px) {
            .hero-band {
                grid-template-columns: 1fr;
            }

            .toolbar-line {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidenav -->
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
            <h1>Manage Users</h1>
        </div>

        <div class="hero-band">
            <div class="hero-card">
                <h2 style="margin-top:0;">User Operations</h2>
                <p class="mini-note" style="margin-top:10px;">
                    Adjust auth roles without changing membership state, and keep destructive account actions visible and deliberate.
                </p>
            </div>
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="mini-note">What lives here</span>
                    <strong>Roles + access</strong>
                </div>
                <div class="summary-card">
                    <span class="mini-note">Membership stays separate</span>
                    <strong>Roles is not membership</strong>
                </div>
            </div>
        </div>

        <div class="surface-panel">
            <p class="mini-note">Use this table to manage account roles while keeping the club membership workflow independent.</p>
            <div class="toolbar-line">
                <div>
                    <label for="userFilter">Filter users</label>
                    <input type="text" id="userFilter" placeholder="Search by username or email">
                </div>
                <div>
                    <label for="userSort">Sort users</label>
                    <select id="userSort">
                        <option value="name_asc">Username A-Z</option>
                        <option value="name_desc">Username Z-A</option>
                        <option value="role">Role</option>
                        <option value="membership">Membership</option>
                        <option value="id_desc">Newest first</option>
                        <option value="id_asc">Oldest first</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="surface-panel">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Membership</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="users-list">
                    <!-- Users will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let allUsers = [];

        function formatMembershipStatus(value) {
            return (value || 'not_submitted').replaceAll('_', ' ');
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function syncRoleSelectAppearance(select) {
            if (!select) {
                return;
            }

            select.classList.remove('role-player', 'role-manager', 'role-admin');
            select.classList.add(`role-${select.value}`);
        }

        function sortUsers(users) {
            const sortMode = document.getElementById('userSort')?.value || 'name_asc';
            return [...users].sort((left, right) => {
                if (sortMode === 'name_desc') {
                    return (right.user_name || '').localeCompare(left.user_name || '', undefined, { sensitivity: 'base' });
                }
                if (sortMode === 'role') {
                    return (left.user_role || '').localeCompare(right.user_role || '', undefined, { sensitivity: 'base' })
                        || (left.user_name || '').localeCompare(right.user_name || '', undefined, { sensitivity: 'base' });
                }
                if (sortMode === 'membership') {
                    return (left.membership_status || 'not_submitted').localeCompare(right.membership_status || 'not_submitted', undefined, { sensitivity: 'base' })
                        || (left.user_name || '').localeCompare(right.user_name || '', undefined, { sensitivity: 'base' });
                }
                if (sortMode === 'id_desc') {
                    return Number(right.user_id || 0) - Number(left.user_id || 0);
                }
                if (sortMode === 'id_asc') {
                    return Number(left.user_id || 0) - Number(right.user_id || 0);
                }

                return (left.user_name || '').localeCompare(right.user_name || '', undefined, { sensitivity: 'base' });
            });
        }

        function renderUsers() {
            const usersList = document.getElementById('users-list');
            const filterValue = (document.getElementById('userFilter')?.value || '').trim().toLowerCase();
            const filteredUsers = allUsers.filter((user) => {
                const haystack = `${user.user_name || ''} ${user.email || ''}`.toLowerCase();
                return haystack.includes(filterValue);
            });

            const sortedUsers = sortUsers(filteredUsers);
            if (sortedUsers.length === 0) {
                usersList.innerHTML = `
                    <tr>
                        <td colspan="6">No users match the current filter.</td>
                    </tr>
                `;
                return;
            }

            usersList.innerHTML = sortedUsers.map((user) => {
                const membershipStatus = user.membership_status || 'not_submitted';
                return `
                    <tr>
                        <td>${escapeHtml(user.user_id)}</td>
                        <td>${escapeHtml(user.user_name)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>
                            <div class="status-stack">
                                <span class="status-note">Auth role</span>
                                <select class="role-pill-select role-${escapeHtml(user.user_role)}" onchange="updateUserRole(${Number(user.user_id)}, this.value); syncRoleSelectAppearance(this);">
                                    <option value="player" ${user.user_role === 'player' ? 'selected' : ''}>Player</option>
                                    <option value="manager" ${user.user_role === 'manager' ? 'selected' : ''}>Manager</option>
                                    <option value="admin" ${user.user_role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <div class="status-stack">
                                <span class="status-note">Club membership</span>
                                <span class="status-pill membership-${escapeHtml(membershipStatus)}">${escapeHtml(formatMembershipStatus(membershipStatus))}</span>
                            </div>
                        </td>
                        <td>
                            <button onclick="deleteUser(${Number(user.user_id)})" class="cancel-btn">Delete user</button>
                        </td>
                    </tr>
                `;
            }).join('');

            document.querySelectorAll('.role-pill-select').forEach(syncRoleSelectAppearance);
        }

        function loadUsers() {
            fetch('../../services/get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        const message = data.message || data.error || 'Failed to load users.';
                        throw new Error(message);
                    }

                    allUsers = data;
                    renderUsers();
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('users-list').innerHTML = `
                        <tr>
                            <td colspan="6">Failed to load users: ${escapeHtml(error.message)}</td>
                        </tr>
                    `;
                });
        }

        function updateUserRole(userId, newRole) {
            fetch('../../services/update_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    new_role: newRole
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User role updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                    loadUsers(); // Reload to reset the select
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating user role. Please try again.');
                loadUsers(); // Reload to reset the select
            });
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('../../services/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        loadUsers();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting user. Please try again.');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('userFilter')?.addEventListener('input', renderUsers);
            document.getElementById('userSort')?.addEventListener('change', renderUsers);
            loadUsers();
        });
    </script>
</body>
</html> 
