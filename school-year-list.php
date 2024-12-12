<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$sql = "SELECT * FROM school_year ORDER BY start_year DESC";
$syResult = $conn->query($sql);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include_once "navbar.php"?>
                <div class="container-fluid">
                    <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">School Year List</h1>
                    <p class="mb-4">DataTables is a third party plugin that is used to generate the demo table below.
                        For more information about DataTables, please visit the <a target="_blank"
                            href="https://datatables.net">official DataTables documentation</a>.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">School Year List</h6>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                            <a href="addSchoolYear.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add School Year
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <!-- <th>Action</th> -->
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>School Year</th>
                                            <!-- <th>Action</th> -->
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php 
                                            if ($syResult->num_rows > 0) {
                                                // Output data of each row
                                                while($row = $syResult->fetch_assoc()) {
                                                    echo "
                                                        <tr>
                                                            <td> S.Y. ".$row["start_year"]." - ".$row["end_year"]." ( ".$row['start_month']."/".$row["start_year"]." - ".$row['end_month']."/".$row["end_year"]." ) </td>
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