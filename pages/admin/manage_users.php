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

        @media (max-width: 900px) {
            .hero-band {
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
                    <strong>`user_role` != membership</strong>
                </div>
            </div>
        </div>

        <div class="surface-panel">
            <p class="mini-note">Use this table to manage account roles while keeping the club membership workflow independent.</p>
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
        function loadUsers() {
            fetch('../../services/get_users.php')
                .then(response => response.json())
                .then(data => {
                    const usersList = document.getElementById('users-list');
                    usersList.innerHTML = '';

                    if (!Array.isArray(data)) {
                        const message = data.message || data.error || 'Failed to load users.';
                        throw new Error(message);
                    }
                    
                    data.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${user.user_id}</td>
                            <td>${user.user_name}</td>
                            <td>${user.email}</td>
                            <td>
                                <select onchange="updateUserRole(${user.user_id}, this.value)">
                                    <option value="player" ${user.user_role === 'player' ? 'selected' : ''}>Player</option>
                                    <option value="manager" ${user.user_role === 'manager' ? 'selected' : ''}>Manager</option>
                                    <option value="admin" ${user.user_role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </td>
                            <td>${user.membership_status || 'not_submitted'}</td>
                            <td>
                                <button onclick="deleteUser(${user.user_id})" class="button delete-btn">Delete</button>
                            </td>
                        `;
                        usersList.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('users-list').innerHTML = `
                        <tr>
                            <td colspan="6">Failed to load users: ${error.message}</td>
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

        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html> 
