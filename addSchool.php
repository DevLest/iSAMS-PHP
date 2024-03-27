<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $schoolName = $conn->real_escape_string($_POST['schoolName']);
    $schoolAddress = $conn->real_escape_string($_POST['schoolAddress']);

    // Check if the school already exists
    $sql = "SELECT id FROM schools WHERE name = '$schoolName'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // School already exists
        echo '<div class="alert alert-warning">A school with the same name already exists.</div>';
    } else {
        // Insert new school
        $sql = "INSERT INTO schools (name, address) VALUES ('$schoolName', '$schoolAddress')";
        if ($conn->query($sql) === TRUE) {
            header("Location: school-list.php");
            exit; // Ensure no further execution in case of redirection   
            echo '<div class="alert alert-success">New school added successfully.</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $sql . '<br>' . $conn->error . '</div>';
        }
    }
}
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
                        <h1 class="h3 mb-2 text-gray-800">Schools</h1>
                        <p class="mb-4">Add, View, Update, and Delete Schools.</p>

                        <!-- Form for adding/updating a school -->
                        <form action="addSchool.php" method="post">
                            <div class="form-group">
                                <label for="schoolName">School Name</label>
                                <input type="text" class="form-control" id="schoolName" name="schoolName" required>
                            </div>
                            <div class="form-group">
                                <label for="schoolAddress">Address</label>
                                <input type="text" class="form-control" id="schoolAddress" name="schoolAddress" required>
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

    <?php include_once "logout-modal.php"?>
    <?php include_once "footer.php"?>

</body>

</html>