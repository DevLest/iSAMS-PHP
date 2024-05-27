<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$schoolName = "";
$schoolLocation = "";
$id = (isset($_GET["id"])) ? trim($_GET["id"]) : "";

if(isset($_GET["type"]) && !empty(trim($_GET["type"]))){
    $type = trim($_GET["type"]);
    if($type == "edit"){
        $sql = "SELECT * FROM schools WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $schoolName = $row['name'];
            $schoolLocation = $row['address'];
        }
    }
    if($type == "delete"){
        $sql = "DELETE FROM schools WHERE id = '$id'";
        $deleteUser = $conn->query($sql);
        echo '<script>alert("School has been Deteled.");</script>';
        header("Location: school-list.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schoolName = $conn->real_escape_string($_POST['schoolName']);
    $schoolAddress = $conn->real_escape_string($_POST['schoolAddress']);
    $id = $_POST['id']; 
    
    if(!empty($id)) {
        $sql = "SELECT * FROM schools WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $sql = "UPDATE schools SET name = '$schoolName', address = '$schoolAddress' WHERE id = $id";
        } else {
            echo '<script>alert("No School found with the provided ID.");</script>';
        }
    } else {
        $sql = "SELECT id FROM schools WHERE name = '$schoolName'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo '<div class="alert alert-warning">A school with the same name already exists.</div>';
        } else {
            $sql = "INSERT INTO schools (name, address) VALUES ('$schoolName', '$schoolAddress')";
        }
    }
    
    if ($conn->query($sql) === TRUE) {
        echo '<script>alert("School data saved successfully.");</script>';
        header("Location: school-list.php");
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

                        <h1 class="h3 mb-2 text-gray-800">Schools</h1>
                        <p class="mb-4">Add, View, Update, and Delete Schools.</p>

                        <form action="addSchool.php" method="post">
                            <input type="hidden" id="id" name="id" value="<?php echo $id;?>">
                            <div class="form-group">
                                <label for="schoolName">School Name</label>
                                <input type="text" class="form-control" id="schoolName" name="schoolName" required value="<?php echo $schoolName;?>">
                            </div>
                            <div class="form-group">
                                <label for="schoolAddress">Address</label>
                                <input type="text" class="form-control" id="schoolAddress" name="schoolAddress" required value="<?php echo $schoolLocation;?>">
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