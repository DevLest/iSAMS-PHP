<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$startYear = "";
$startMonth = "";
$endMonth = "";
$endYear = "";
$id = (isset($_GET["id"])) ? trim($_GET["id"]) : "";

if(isset($_GET["type"]) && !empty(trim($_GET["type"]))){
    $type = trim($_GET["type"]);
    if($type == "edit"){
        $sql = "SELECT * FROM school_year WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $startYear = $row['start_year'];
            $startMonth = $row['start_month'];
            $endMonth = $row['end_month'];
            $endYear = $row['end_year'];
        }
    }
    if($type == "delete"){
        $sql = "DELETE FROM schools WHERE id = '$id'";
        $deleteUser = $conn->query($sql);
        echo '<script>alert("School Year has been Deleted.");</script>';
        header("Location: school-year-list.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $startYear = $conn->real_escape_string($_POST['startYear']);
    $startMonth = $conn->real_escape_string($_POST['startMonth']);
    $endMonth = $conn->real_escape_string($_POST['endMonth']);
    $endYear = $conn->real_escape_string($_POST['endYear']);
    $id = $_POST['id']; 
    
    if(!empty($id)) {
        $sql = "SELECT * FROM school_year WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $sql = "UPDATE school_year SET start_year = '$startYear', start_month = '$startMonth', end_month = '$endMonth', end_year = '$endYear' WHERE id = $id";
        } else {
            echo '<script>alert("No School found with the provided ID.");</script>';
        }
    } else {
        // $sql = "SELECT id FROM school_year WHERE name = '$schoolName'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     echo '<div class="alert alert-warning">A school with the same name already exists.</div>';
        // } else {
            $sql = "INSERT INTO school_year (start_year, start_month, end_month, end_year) VALUES ('$startYear', '$startMonth', '$endMonth', '$endYear')";
        // }
    }
    
    if ($conn->query($sql) === TRUE) {
        echo '<script>alert("School Year data saved successfully.");</script>';
        header("Location: school-year-list.php");
        exit;
        echo '<div class="alert alert-success">New school added successfully.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . $sql . '<br>' . $conn->error . '</div>';
    }

}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include_once "navbar.php"?>
                <div class="container-fluid">
                    <div class="container-fluid">

                        <h1 class="h3 mb-2 text-gray-800">School Year</h1>
                        <p class="mb-4">Add, View, Update, and Delete Schools.</p>

                        <form action="addSchoolYear.php" method="post">
                            <input type="hidden" id="id" name="id" value="<?php echo $id;?>">
                            <div class="form-group">
                                <label for="startMonth">Start Month</label>
                                <input type="text" class="form-control" id="startMonth" name="startMonth" required value="<?php echo $startMonth;?>">
                            </div>
                            <div class="form-group">
                                <label for="startYear">Start Year</label>
                                <input type="text" class="form-control" id="startYear" name="startYear" required value="<?php echo $startYear;?>">
                            </div>
                            <div class="form-group">
                                <label for="endMonth">End Month</label>
                                <input type="text" class="form-control" id="endMonth" name="endMonth" required value="<?php echo $endMonth;?>">
                            </div>
                            <div class="form-group">
                                <label for="endYear">End Year</label>
                                <input type="text" class="form-control" id="endYear" name="endYear" required value="<?php echo $endYear;?>">
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

    <?php include_once "logout-modal.php"?>
    <?php include_once "footer.php"?>

</body>

</html>