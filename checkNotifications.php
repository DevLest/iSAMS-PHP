<?php
session_start();
require_once "connection/db.php";

if ($_SESSION['role'] != 1) { // Only allow admins to access this
    echo json_encode(['count' => 0, 'requests' => []]);
    exit;
}

$pendingRequestsQuery = "SELECT COUNT(*) as count FROM edit_requests WHERE status = 'pending'";
$result = $conn->query($pendingRequestsQuery);
$pendingCount = $result->fetch_assoc()['count'];

$requestsQuery = "SELECT er.*, uc.username AS user_name, ic.issues FROM edit_requests er JOIN users uc ON er.requested_by = uc.id JOIN issues_and_concerns ic ON er.issue_id = ic.id WHERE er.status = 'pending'";
$requests = $conn->query($requestsQuery);
$requestsArray = $requests->fetch_all(MYSQLI_ASSOC);

echo json_encode(['count' => $pendingCount, 'requests' => $requestsArray]);
?>
