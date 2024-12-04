<?php
session_start();
require_once "connection/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'denied';

    // Get the referrer before processing the request
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : 'index.php';

    // Update the edit request status
    $stmt = $conn->prepare("UPDATE edit_requests SET status = ?, processed_by = ?, processed_date = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $request_id);
    
    if ($stmt->execute()) {
        // Get the request details for notification
        $requestQuery = "SELECT er.*, u.username, u.first_name, u.last_name 
                        FROM edit_requests er 
                        JOIN users u ON er.requested_by = u.id 
                        WHERE er.id = ?";
        $stmt2 = $conn->prepare($requestQuery);
        $stmt2->bind_param("i", $request_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $request = $result->fetch_assoc();
        
        // Store notification in session
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        
        $_SESSION['notifications'][] = [
            'message' => "Edit request for {$request['type']} has been {$status}",
            'time' => time()
        ];
        
        $_SESSION['success_message'] = "Request processed successfully";
    } else {
        $_SESSION['error_message'] = "Failed to process request";
    }
    
    $stmt->close();
    header("Location: " . $return_url);
    exit;
}
?>
