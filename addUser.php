<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";

$username = "";
$firstName = "";
$lastName = "";
$roleID = "";
$id = (isset($_GET["id"])) ? trim($_GET["id"]) : "";

if(isset($_GET["type"]) && !empty(trim($_GET["type"]))){
    $type = trim($_GET["type"]);
    if($type == "edit"){
        $sql = "SELECT * FROM users WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $username = $row['username'];
            $firstName = $row['first_name'];
            $lastName = $row['last_name'];
            $roleID = $row['role'];
        }
    }
    if($type == "delete"){
        $sql = "DELETE FROM users WHERE id = '$id'";
        $deleteUser = $conn->query($sql);
        echo '<script>alert("User has been Deteled.");</script>';
        header("Location: user-list.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password']; 
    $id = $_POST['id']; 

    if(!empty($id)) {
        $sql = "SELECT * FROM users WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            if(!empty($password)) {
                $hashedPassword = md5($password);
                $sql = "UPDATE users SET username = '$username', first_name = '$firstName', last_name = '$lastName', role = '$role', password = '$hashedPassword' WHERE id = $id";
            } else {
                $sql = "UPDATE users SET username = '$username', first_name = '$firstName', last_name = '$lastName', role = '$role' WHERE id = $id";
            }
        } else {
            echo '<script>alert("No user found with the provided ID.");</script>';
        }
    } else {
        $sql = "SELECT id FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo '<script>alert("User already exists.");</script>';
        } else {
            $hashedPassword = md5($password);
            $sql = "INSERT INTO users (username, first_name, last_name, role, password) VALUES ('$username', '$firstName', '$lastName', '$role', '$hashedPassword')";
        }
    }
    
    if ($conn->query($sql) === TRUE) {
        echo '<script>alert("User data saved successfully.");</script>';
        header("Location: user-list.php");
        exit;
    } else {
        echo '<script>alert("Error: " . $conn->error);</script>';
    }
}
$roles = [];

$sql = "SELECT * FROM roles";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}

include_once('header.php');
?>

<body id="page-top">

    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include_once "navbar.php"?>
                <div class="container-fluid">

                    <div class="container-fluid">

                        <h1 class="h3 mb-2 text-gray-800">User</h1>
                        <p class="mb-4">Add, View, Update, and Delete Schools.</p>

                        <form action="addUser.php" method="post">
                            <input type="hidden" id="id" name="id" required value="<?php echo $id?>">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo $username?>">
                            </div>
                            <div class="form-group position-relative">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" <?php if(!(isset($type) && $type == 'edit')) echo 'required'; ?>>
                                <span class="eye-icon" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required value="<?php echo $firstName?>">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required value="<?php echo $lastName?>">
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select a role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['id']); ?>" <?php if($role['id'] == $roleID) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($role['description']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>

                    </div>

                </div>

            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; YUMI 2024</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>
    
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <style>
        .form-group.position-relative {
            display: flex;
            align-items: center;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .form-control {
            padding-right: 30px;
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