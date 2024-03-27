<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password']; 

    // Check if the user already exists
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo '<script>alert("User already exists.");</script>';
    } else {
        // Hash the password for security
        $hashedPassword = md5($password);

        // Insert new user
        $sql = "INSERT INTO users (username, first_name, last_name, role, password) VALUES ('$username', '$firstName', '$lastName', '$role', '$hashedPassword')";
        if ($conn->query($sql) === TRUE) {
            header("Location: user-list.php"); // Redirect to user list page on success
            exit;
        } else {
            echo '<script>alert("Error: ' . $conn->error . '");</script>';
        }
    }
}

$roles = [];

// Fetch roles from the database
$sql = "SELECT * FROM roles"; // Adjust if your table or column names differ
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $roles[] = $row; // Add each role to the roles array
    }
}

include_once('header.php');
?>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <?php include_once "navbar.php"?>
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Begin Page Content -->
                    <div class="container-fluid">

                        <!-- Page Heading -->
                        <h1 class="h3 mb-2 text-gray-800">User</h1>
                        <p class="mb-4">Add, View, Update, and Delete Schools.</p>

                        <form action="addUser.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group position-relative">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="eye-icon" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select a role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['id']); ?>"><?php echo htmlspecialchars($role['description']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>

                        <!-- Here we will insert PHP code to display schools -->

                    </div>
                    <!-- /.container-fluid -->

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <style>
        .form-group.position-relative {
            display: flex;
            align-items: center; /* Aligns items vertically center in a flex container */
        }

        .eye-icon {
            position: absolute;
            right: 10px; /* Adjust this as necessary */
            cursor: pointer;
            display: flex;
            align-items: center; /* Helps center the icon vertically */
        }

        /* Adjust height or padding as needed based on your form's design */
        .form-control {
            padding-right: 30px; /* Makes room for the icon inside the input box */
        }
    </style>

    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById('password');
            var eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>

    <?php include_once "logout-modal.php"?>
    <?php include_once "footer.php"?>

</body>

</html>