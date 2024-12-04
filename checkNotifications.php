<?php
session_start();
require_once "connection/db.php";

$response = ['count' => 0, 'requests' => []];

if ($_SESSION['role'] == 1) { // Admin
    $query = "SELECT er.*, 
              COALESCE(u1.username, 'Unknown User') as requester_username,
              COALESCE(u2.username, 'Unknown User') as processor_username,
              gr.name as grade_level_name
              FROM edit_requests er 
              LEFT JOIN users u1 ON er.requested_by = u1.id
              LEFT JOIN users u2 ON er.processed_by = u2.id 
              LEFT JOIN grade_level gr ON er.grade_level = gr.id
              WHERE er.status = 'pending'";
    $result = $conn->query($query);
    
    $response['count'] = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $row['username'] = $row['requester_username'] ?: 'Unknown User';
        $row['processor'] = $row['processor_username'] ?: 'Unknown User';
        $response['requests'][] = $row;
    }
} else { // Regular user
    $query = "SELECT er.*,
              COALESCE(u1.username, 'Unknown User') as requester_username,
              COALESCE(u2.username, 'Unknown User') as processor_username,
              gr.name as grade_level_name
              FROM edit_requests er
              LEFT JOIN users u1 ON er.requested_by = u1.id
              LEFT JOIN users u2 ON er.processed_by = u2.id
              LEFT JOIN grade_level gr ON er.grade_level = gr.id
              WHERE er.requested_by = ? AND er.status IN ('approved', 'denied')
              AND er.processed_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response['count'] = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $row['username'] = $row['requester_username'] ?: 'Unknown User';
        $row['processor'] = $row['processor_username'] ?: 'Unknown User';
        $response['requests'][] = $row;
    }
}

echo json_encode($response);
?>
