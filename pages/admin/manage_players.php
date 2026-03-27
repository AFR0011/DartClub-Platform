<?php
require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';

app_start_session();
require_any_role(['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Players</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
    <script src="../../js/select_row.js"></script>
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
        <h1>Player Applications</h1>
        <table>
            <tr>
                <th>Apply #</th>
                <th>Date</th>
                <th colspan="4">Actions</th>
            </tr>

            <!-- PHP loop to display applications -->
<?php
// Function to delete both the application file and the corresponding database entry
function deleteApplication($appId, $appPath, $db)
{
    // Delete the application file
    if (unlink($appPath)) {
        // Prepare a statement to delete the application from the database
        $query = "DELETE FROM applications WHERE app_id = ?";
        $stmt = $db->prepare($query);

        // Bind parameters
        $stmt->bind_param("i", $appId);

        // Execute the statement
        if ($stmt->execute()) {
            return true; // Deletion successful
        } else {
            return false; // Deletion failed
        }
    } else {
        return false; // Unable to delete file
    }
}

// Check if delete button is clicked
if (isset($_GET['delete'])) {
    $appIdToDelete = $_GET['delete'];
    $appPathToDelete = $_GET['app_path'];

    // Delete the application and its file
    if (deleteApplication($appIdToDelete, $appPathToDelete, $db)) {
        echo "<script>alert('Application and associated file deleted successfully');</script>";
    } else {
        echo "<script>alert('Failed to delete application and file');</script>";
    }
}

// Prepare a statement to retrieve application data
$query = "SELECT app_id, app_path, app_creationDate FROM applications";
$stmt = $db->prepare($query);

// Check if the statement was prepared successfully
if ($stmt) {
    // Execute the statement
    $stmt->execute();

    // Bind result variables
    $stmt->bind_result($app_id, $app_path, $app_creationDate);

    // Fetch and display applications
    while ($stmt->fetch()) {
        echo '<tr onclick="selectRow(this)">';
        echo "<td>" . $app_id . "</td>";
        echo "<td>" . $app_creationDate . "</td>";
        echo "<td><a href='" . $app_path . "' class='application-btn'>Download</a></td>";
        echo "<td><a href='#' onclick='viewApplication(\"$app_path\")' class='application-btn'>View</a></td>";
        echo "<td><a href='#' onclick='approveApplication(" . $app_id . ")' class='application-btn'>Approve</a></td>";
        echo '<td><a href="?delete=' . $app_id . '&app_path=' . urlencode($app_path) . '" onclick="return confirm(\'Are you sure you want to delete this application?\')" class="application-btn delete">Reject</a></td>';
        echo "</tr>";
    }

    // Close the statement
    $stmt->close();
} else {
    // Statement preparation failed
    echo "Error: " . $db->error;
}

?>
        </table>
    </div>
    <form action="../../services/create_player.php" method="post" id="registrationForm">
    <!-- iframe to display application file-->
    <div id="applicationFrame" style="display: none;">
        <iframe id="iframe" width="80%" height="500px" frameborder="0"></iframe>
    </div>
    
    <div class="container">
        <h1>Add Player</h1>
        <p id="formHint" style="color:#555;">Select an application and click Approve to preselect it, then fill details and submit.</p>
        <div>
            <label for="fedRegNumber">Fed. Registeration number:</label>
            <input type="text" name="fedRegNumber" id="fedRegNumber">
        </div>
        <div>
            <label for="trncId">TRNC Identity Number:</label>
            <input type="text" name="trncId" id="trncId">
        </div>
            <div>
                <label for="fName">First Name:</label>
                <input type="text" name="fName" id="fName">
            </div>

            <div>
                <label for="lName">Last Name:</label>
                <input type="text" name="lName" id="lName">
            </div>
            <div>
                <label for="fatherName">Father's Name:</label>
                <input type="text" name="fatherName" id="fatherName">
            </div>
            <div>
                <label for="motherName">Mother's Name:</label>
                <input type="text" name="motherName" id="motherName">
            </div>
            <div>
                <label for="birthPlace">Place of Birth:</label>
                <input type="text" name="birthPlace" id="birthPlace">
            </div>
            <div>
                <label for="birthDate">Date of Birth:</label>
                <input type="date" name="birthDate" id="birthDate">
            </div>
            <div>
                <label for="phoneNo">Phone Number:</label>
                <input type="tel" name="phoneNo" id="phoneNo">
            </div>
            <div>
                <label for="address">Address:</label>
                <input type="text" name="address" id="address">
            </div>
            <div>
                <label for="passNo">Passport No:</label>
                <input type="text" name="passNo" id="passNo">
            </div>
            <div>
                <label for="resPermitNo">Residency Permit No:</label>
                <input type="text" name="resPermitNo" id="resPermitNo">
            </div>

            <div>
                <label for="username">Username:</label>
                <input type="text" name="username" id="username">
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email">
            </div>
            <input type="hidden" name="appId" id="appId">
            <button type="submit">Register</button>
            </form>
    </div>

    <script>
        function viewApplication(path){
            const iframe = document.getElementById('iframe');
            document.getElementById('applicationFrame').style.display = 'block';
            iframe.src = path;
            window.scrollTo({ top: document.getElementById('applicationFrame').offsetTop - 20, behavior: 'smooth' });
        }
        function approveApplication(id){
            document.getElementById('appId').value = id;
            const hint = document.getElementById('formHint');
            if (hint) hint.textContent = 'Application #' + id + ' selected. Fill the form and submit to create the player.';
            window.scrollTo({ top: document.getElementById('registrationForm').offsetTop - 20, behavior: 'smooth' });
        }
    </script>
</body>

</html>
