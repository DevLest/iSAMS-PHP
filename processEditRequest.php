<?php
session_start();
require_once "connection/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    $status = ($action === 'approve') ? 'approved' : 'denied';

    // Update the edit request status
    $stmt = $conn->prepare("UPDATE edit_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    $stmt->execute();
    $stmt->close();

    // Notify the user if approved
    if ($action === 'approve') {
        // Here you can implement a notification system to inform the user
        // For example, you could insert a record into a notifications table
    }

    header("Location: adminEditRequests.php");
    exit;
}
?>
