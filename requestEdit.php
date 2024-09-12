<?php
session_start();
require_once "connection/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id']) && isset($_POST['user_id'])) {
    $issue_id = (int)$_POST['issue_id'];
    $user_id = (int)$_POST['user_id'];
    $requested_by = $_SESSION['user_id']; // Store who requested the edit

    // Insert the edit request
    $stmt = $conn->prepare("INSERT INTO edit_requests (user_id, issue_id, requested_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $issue_id, $requested_by);
    $stmt->execute();
    $stmt->close();

    // Notify the user
    // You can implement a notification system here, e.g., sending an email or updating a notifications table

    echo json_encode(['message' => 'Edit request submitted successfully.']);
} else {
    echo json_encode(['message' => 'Invalid request.']);
}
?>
