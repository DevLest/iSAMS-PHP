<?php
session_start();

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once "connection/db.php";

$username = $password = "";
$username_err = $password_err = "";
$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, first_name, last_name, password FROM users WHERE username = ?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_username);

            $param_username = $username;

            if($stmt->execute()){
                $stmt->store_result();

                if($stmt->num_rows == 1){
                    $stmt->bind_result($id, $username, $first_name, $last_name, $hashed_password);
                    if($stmt->fetch()){
                        if(md5($password) === $hashed_password){
                            session_start();
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["fullname"] = $first_name . " " . $last_name;

                            header("location: dashboard.php");
                        } else{
                            $error = "The password you entered was not valid.";
                        }
                    }
                } else{
                    $error = "No account found with that username.";
                }
            } else{
                $error = "Oops! Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }

    $conn->close();
}

include_once('header.php');
?>


    <body class="bg-gradient-primary" style="display: flex; flex-direction: column; justify-content: center; min-height: 100vh; background-image: url('img/login-bg.jpg'); background-size: cover; background-position: center;">

        <div class="container">

            <!-- Outer Row -->
            <div class="row justify-content-center">

                <div class="col-xl-10 col-lg-12 col-md-9">

                    <div class="card o-hidden border-0 shadow-lg my-5" style="background-color: rgba(255, 255, 255, 0.8);">
                        <div class="card-body p-0">
                            <!-- Nested Row within Card Body -->
                            <div class="row">
                                <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                                <div class="col-lg-6">
                                    <div class="p-5">
                                        <div class="text-center">
                                            <h1 class="h4 text-gray-900 mb-4">Welcome To SMEA</h1>
                                            <?php
                                                if(isset($error) && $error != ""){
                                                    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
                                                }
                                            ?>
                                        </div>
                                        <form class="user" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                            <div class="form-group">
                                                <input type="text" class="form-control form-control-user" id="username" name="username" aria-describedby="emailHelp" placeholder="Enter Username...">
                                            </div>
                                            <div class="form-group">
                                                <input type="password" class="form-control form-control-user" id="password" name="password" placeholder="Password">
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- Bootstrap core JavaScript-->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Core plugin JavaScript-->
        <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

        <!-- Custom scripts for all pages-->
        <script src="js/sb-admin-2.min.js"></script>

    </body>

    </html>