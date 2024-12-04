<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { // Assuming role 1 is Admin
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";

// Fetch edit requests
$requestsQuery = "SELECT er.*, u.username AS user_name 
                 FROM edit_requests er 
                 JOIN users u ON er.requested_by = u.id 
                 WHERE er.status = 'pending'";
$requests = $conn->query($requestsQuery);

// Check if there are no pending requests
if ($requests->num_rows == 0) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Requests</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Edit Requests</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Grade Level</th>
                    <th>Gender</th>
                    <th>Reason</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <form action="processEditRequest.php" method="post">
                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="return_url" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                            <button type="submit" name="action" value="deny" class="btn btn-danger">Deny</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
