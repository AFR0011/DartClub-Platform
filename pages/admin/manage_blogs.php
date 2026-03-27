<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blogs</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
</head>
<body>
    <!-- Sidenav -->
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="../main.php">Back to Website</a>
    </div>
    <div class="container">
        <div class="header-actions">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
            <h1>Manage Blogs</h1>
        </div>

        <div class="callout" style="margin:16px 0;padding:12px 16px;border-left:4px solid #1f6feb;background:#eef5ff;">
            This is a legacy admin page. Blog data now comes from the paginated service layer, so this view only shows the latest page of results.
        </div>

        <!-- Create New Blog Form -->
        <div class="form-section">
            <h2>Create New Blog Post</h2>
            <form id="create-blog-form">
                <div class="form-group">
                    <label for="blog-title">Title:</label>
                    <input type="text" id="blog-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="blog-content">Content:</label>
                    <textarea id="blog-content" name="content" rows="6" required></textarea>
                </div>
                <button type="submit" class="button">Create Blog Post</button>
            </form>
        </div>

        <!-- Existing Blogs -->
        <div class="table-container">
            <h2>Existing Blog Posts</h2>
            <table class="blogs-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="blogs-list">
                    <!-- Blogs will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function loadBlogs() {
            fetch('../../services/get_blogs.php')
                .then(response => response.json())
                .then(data => {
                    const blogsList = document.getElementById('blogs-list');
                    blogsList.innerHTML = '';

                    (data.items || []).forEach(blog => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${blog.blog_title}</td>
                            <td>${blog.blog_content.substring(0, 100)}${blog.blog_content.length > 100 ? '...' : ''}</td>
                            <td>
                                <button onclick="deleteBlog(${blog.blog_id})" class="button delete-btn">Delete</button>
                            </td>
                        `;
                        blogsList.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading blogs:', error);
                });
        }

        function deleteBlog(blogId) {
            if (confirm('Are you sure you want to delete this blog post?')) {
                fetch('../../services/delete_blog.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        blog_id: blogId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Blog post deleted successfully!');
                        loadBlogs();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting blog post. Please try again.');
                });
            }
        }

        document.getElementById('create-blog-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const title = document.getElementById('blog-title').value;
            const content = document.getElementById('blog-content').value;
            
            fetch('../../services/create_blog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    title: title,
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Blog post created successfully!');
                    document.getElementById('blog-title').value = '';
                    document.getElementById('blog-content').value = '';
                    loadBlogs();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating blog post. Please try again.');
            });
        });

        document.addEventListener('DOMContentLoaded', loadBlogs);
    </script>
</body>
</html> 
