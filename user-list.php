<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$sql = "SELECT users.*, roles.description, schools.name as school FROM users JOIN roles ON users.role = roles.id LEFT JOIN schools ON users.school_id = schools.id ORDER BY users.created_at DESC";
$resultUsers = $conn->query($sql);
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
                    <h1 class="h3 mb-2 text-gray-800">User List</h1>
                    <p class="mb-4">DataTables is a third party plugin that is used to generate the demo table below.
                        For more information about DataTables, please visit the <a target="_blank"
                            href="https://datatables.net">official DataTables documentation</a>.</p>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Active Users</h6>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                                    <a href="addUser.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add User
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Fullname</th>
                                            <th>Role</th>
                                            <th>School</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Username</th>
                                            <th>Fullname</th>
                                            <th>Role</th>
                                            <th>School</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php 
                                            if ($resultUsers->num_rows > 0) {
                                                // Output data of each row
                                                while($row = $resultUsers->fetch_assoc()) {
                                                    echo "
                                                        <tr>
                                                            <td>".$row["username"]."</td>
                                                            <td>".$row["first_name"]. " ".$row["last_name"]."</td>
                                                            <td>".$row["description"]."</td>
                                                            <td>".$row["school"]."</td>
                                                            <td>";
                                                    echo isset($_SESSION['role']) && $_SESSION['role'] == 1 ? "
                                                            <a href='addUser.php?id=".$row["id"]."&type=edit' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i> Edit</a>
                                                            <a href='addUser.php?id=".$row["id"]."&type=delete' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i> Delete</a>" : "";
                                                    echo " </td>
                                                        </tr>
                                                    ";
                                                }
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; YUMI 2024</span>
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