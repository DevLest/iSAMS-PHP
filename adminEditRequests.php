<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { // Assuming role 1 is Admin
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";

// Fetch edit requests
$requestsQuery = "SELECT er.*, uc.username AS user_name, ic.issues FROM edit_requests er JOIN users uc ON er.user_id = uc.id JOIN issues_and_concerns ic ON er.issue_id = ic.id WHERE er.status = 'pending'";
$requests = $conn->query($requestsQuery);
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
                    <th>Issue</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['user_name']; ?></td>
                    <td><?php echo $row['issues']; ?></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <form action="processEditRequest.php" method="post">
                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
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
