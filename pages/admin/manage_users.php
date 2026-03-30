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

        <!-- User Management Table -->
        <div class="table-container">
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
