<?php
session_start();
require_once "connection/db.php";

$response = ['count' => 0, 'requests' => []];

if ($_SESSION['role'] == 1) { // Admin
    $query = "SELECT er.*, u.username 
              FROM edit_requests er 
              JOIN users u ON er.requested_by = u.id 
              WHERE er.status = 'pending'";
    $result = $conn->query($query);
    
    $response['count'] = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $response['requests'][] = $row;
    }
} else { // Regular user
    $query = "SELECT * FROM edit_requests 
              WHERE requested_by = ? AND status IN ('approved', 'denied') 
              AND processed_date > DATE_SUB(NOW(), INTERVAL 1 DAY)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response['count'] = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $response['requests'][] = $row;
    }
}

echo json_encode($response);
?>
