<?php
session_start();
require_once "connection/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = (int)$_POST['school_id'];
    $type = $_POST['type'];
    $grade_level = (int)$_POST['grade_level'];
    $gender = $_POST['gender'];
    $reason = $_POST['reason'];
    $requested_by = $_SESSION['user_id'];

    // Insert the edit request with the correct fields
    $stmt = $conn->prepare("INSERT INTO edit_requests (school_id, type, grade_level, gender, reason, requested_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isissi", $school_id, $type, $grade_level, $gender, $reason, $requested_by);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Edit request submitted successfully. An administrator will review your request.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit edit request.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
