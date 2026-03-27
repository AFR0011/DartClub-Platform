<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Image Upload</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"
        integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
    <script src="../../js/upload.js"></script>
    <script src="../../js/admin_nav.js"></script>
</head>

<body>
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="../main.php">Back to Website</a>
    </div>
    <div class="container">
        <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
        <h1>Image Upload</h1>
    </div>

    <div class="container">
        <form action="upload_handler.php" method="post" enctype="multipart/form-data">
            <section>
                <div id="uploadSection" class="upload container">
                    <div id="uploadArea" class="upload_area container">
                        <div class="upload__content">
                            <input type="file" multiple="true" max="2" id="uploadInput" />
                            <h3>Drag and drop media here</h3>
                            <h3>OR</h3>
                            <button id="browseButton" class="button">Browse Files</button>
                        </div>
                    </div>
                    <div id="uploadFiles" class="upload_files"></div>
                    <br />
                    <button id="uploadButton" class="button">Submit Files</button>
                </div>
            </section>
        </form>
    </div>

</body>

</html>