<?php
if (session_status()) session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
$sessionDestroyed = session_destroy();

// Return a JSON response
header('Content-Type: application/json');
if ($sessionDestroyed) {
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "message" => "Session destruction failed"));
}
exit();