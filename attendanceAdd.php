<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);
$year = date('Y');

if (isset($_POST['quarter'])) {
    $selectedQuarter = $_POST['quarter'];
} else {
    $selectedQuarter = $currentQuarter;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $stmt = $conn->prepare("INSERT INTO attendance_summary (school_id, grade_level_id, gender, type, count, quarter, year, last_user_save) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $gender_male = 1;
    $gender_female = 2;
    $current_user_id = $_SESSION['user_id'];

    list($type, $grade_level_id) = explode('-', $_POST['activeTab']);

    if (isset($_POST[$type.'-male']) || isset($_POST[$type.'-female'])) {

        foreach ($_POST[$type.'-male'] as $school_id => $count) {
            $count = (int) $count;
            if ($count > 0) {
                $query = sprintf(
                    "SELECT * FROM attendance_summary WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s'",
                    mysqli_real_escape_string($conn, $school_id),
                    mysqli_real_escape_string($conn, $grade_level_id),
                    mysqli_real_escape_string($conn, $gender_male),
                    mysqli_real_escape_string($conn, $type),
                    mysqli_real_escape_string($conn, $_POST['quarter']),
                    mysqli_real_escape_string($conn, $year)
                );
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                    $query = sprintf(
                        "UPDATE attendance_summary SET count = '%s' WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s' AND last_user_save = '%s'",
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_male),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year),
                        mysqli_real_escape_string($conn, $current_user_id)
                    );
                } else {
                    $query = sprintf(
                        "INSERT INTO attendance_summary (school_id, grade_level_id, gender, type, count, quarter, year, last_user_save) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_male),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year),
                        mysqli_real_escape_string($conn, $current_user_id)
                    );
                }
                $conn->query($query);
            } else continue;
        }

        foreach ($_POST[$type.'-female'] as $school_id => $count) {
            $count = (int) $count;
            if ($count > 0) {
                $query = sprintf(
                    "SELECT * FROM attendance_summary WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s'",
                    mysqli_real_escape_string($conn, $school_id),
                    mysqli_real_escape_string($conn, $grade_level_id),
                    mysqli_real_escape_string($conn, $gender_female),
                    mysqli_real_escape_string($conn, $type),
                    mysqli_real_escape_string($conn, $_POST['quarter']),
                    mysqli_real_escape_string($conn, $year)
                );
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                    $query = sprintf(
                        "UPDATE attendance_summary SET count = '%s' WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s' AND last_user_save = '%s'",
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_female),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year),
                        mysqli_real_escape_string($conn, $current_user_id)
                    );
                } else {
                    $query = sprintf(
                        "INSERT INTO attendance_summary (school_id, grade_level_id, gender, type, count, quarter, year, last_user_save) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_female),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year),
                        mysqli_real_escape_string($conn, $current_user_id)
                    );
                }
                $conn->query($query);
            } else continue;
        }
    }

    $stmt->close();
}

$sql = "SELECT * FROM schools";

$schools = "";
$schools = $conn->query($sql);

$inputTables = "";

if ($schools->num_rows > 0) {
    while($row = $schools->fetch_assoc()) {
        $inputTables .= "
            <tr>
                <td>".$row["name"]."</td>
                <td><input type='number' class='form-control form-control-sm' name='dynamicId-male[".$row['id']."]' value='0'></td>
                <td><input type='number' class='form-control form-control-sm' name='dynamicId-female[".$row['id']."]' value='0'></td>
                <td class='total'>0</td>
            </tr>
        ";
    }
}

$attendanceQuery = "SELECT attendance_summary.*, users.first_name, users.last_name FROM attendance_summary INNER JOIN users on users.id = attendance_summary.last_user_save WHERE quarter = $selectedQuarter AND year = $year";
$attendanceResult = $conn->query($attendanceQuery);
$attendance = $attendanceResult->fetch_assoc();

$attendanceData = [];
$lastUserSave = "";
foreach ($attendanceResult as $row) {
    $gender = ($row['gender'] == 1) ? 'male' : 'female';
    $keyId = $gender.'-'.$row['type'].'-'.$row['grade_level_id'].'-'.$row['school_id'];
    $attendanceData[$keyId] = $row['count'];
    $lastUserSave = $row['last_name'].', '.$row['first_name'];
}
$attendanceKeys = array_keys($attendanceData);

$sql = "SELECT * FROM grade_level";
$grade_level = $conn->query($sql);

$grade_levels = "";
if ($grade_level->num_rows > 0) {
    $count = 0;
    while($row = $grade_level->fetch_assoc()) {
        $grade_level_inputs = "";
        $active = $count == 0 ? "true" : "false";
        $rowid = $row['id'];

        $grade_levels .= "<a class='nav-link ".($count == 0 ? 'active' : '')."' id='v-pills-dynamicId-$rowid-tab' onclick=\"activeTab('dynamicId-$rowid')\" data-toggle='pill' href='#v-pills-dynamicId-$rowid' role='tab' aria-controls='v-pills-dynamicId-$rowid' aria-selected='$active'>".$row['name']."</a>";
        
        $grade_level_inputs .= "<div class='tab-pane fade show active' id='v-pills-dynamicId-$rowid' role='tabpanel' aria-labelledby='v-pills-dynamicId-$rowid-tab'>
                                    <table class='table'>
                                    <thead>
                                        <tr>
                                            <th scope='col'>Name</th>
                                            <th scope='col'>Male</th>
                                            <th scope='col'>Female</th>
                                            <th scope='col'>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ".$inputTables."
                                    </tbody>
                                </table>
                                </div>";

        $count++;
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
                    
                    <style>
                        .navbar-custom {
                            padding-bottom: 0;
                        }

                        .navbar-custom .navbar-nav {
                            margin-top: 8px;
                        }

                        .navbar-custom .navbar-nav .nav-link {
                            border-radius: 20px 20px 0 0;
                            margin-right: 2px;
                            border: 1px solid transparent;
                            border-bottom: none;
                        }

                        .nav-item-custom.active .nav-link, .nav-link:hover {
                            background-color: white;
                            color: #007bff;
                            border-color: #007bff;
                        }

                        /* New class for active tab content */
                        .active-content-tab {
                            display: block;
                            padding: 20px;
                            margin-top: -1px;
                            border: 1px solid #007bff;
                            color: #007bff;
                            border-radius: 0 0 5px 5px;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                            background-color: white;
                        }

                        .table {
                            border-collapse: collapse;
                            width: 100%;
                            background-color: #fff;
                        }

                        .table thead th {
                            font-weight: 600;
                            background-color: #f8f9fa;
                            color: #333;
                            padding: 12px;
                        }

                        .table tbody td {
                            padding: 10px;
                            color: #555;
                        }

                        .table tbody tr:nth-child(odd) {
                            background-color: #f2f2f2;
                        }

                        .table th, .table td {
                            border: none;
                        }

                        .table tbody tr:hover {
                            background-color: #eaeaea;
                        }

                        .form-control {
                            border-radius: 0.25rem;
                            border: 1px solid #ced4da;
                            box-shadow: none;
                        }

                        .table-responsive {
                            border: none;
                        }

                        .table .form-control {
                            margin: 0;
                            background-color: #fff;
                            color: #495057;
                        }
                        
                        .nav-tabs {
                            border-bottom: 1px solid #dee2e6;
                            margin-bottom: 1rem;
                        }
                        .nav-tabs .nav-item {
                            margin-bottom: -1px;
                        }
                        .nav-tabs .nav-link {
                            border: 1px solid transparent;
                            border-top-left-radius: 0.25rem;
                            border-top-right-radius: 0.25rem;
                            padding: 0.5rem 1rem;
                            color: #495057;
                        }
                        .nav-tabs .nav-link:hover {
                            border-color: #e9ecef #e9ecef #dee2e6;
                        }
                        .nav-tabs .nav-link.active {
                            color: #495057;
                            background-color: #fff;
                            border-color: #dee2e6 #dee2e6 #fff;
                        }
                        .tab-content {
                            padding: 1rem;
                            border: 1px solid #dee2e6;
                            border-top: none;
                            border-radius: 0 0 0.25rem 0.25rem;
                        }
                    </style>

                    <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">Data Encoding</h1>
                    <p class="mb-4">Encode Population or Data head count for Analysis and Comparison.</p>
                    
                    <form action="attendanceAdd.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="quarter">Select Quarter:</label>
                                <select id="quarter" name="quarter">
                                    <option value="1" <?php if ($selectedQuarter == 1) echo 'selected'; ?>>1st</option>
                                    <option value="2" <?php if ($selectedQuarter == 2) echo 'selected'; ?>>2nd</option>
                                    <option value="3" <?php if ($selectedQuarter == 3) echo 'selected'; ?>>3rd</option>
                                    <option value="4" <?php if ($selectedQuarter == 4) echo 'selected'; ?>>4th</option>
                                </select>
                                <button type="submit" class="btn btn-success" name="filter">Select</button>
                            </div>
                            <div class="col-md-6 text-right">
                                Last Edited By: <?php echo $lastUserSave;?>
                                <button type="submit" class="btn btn-primary" name="save">Save</button>
                            </div>
                        </div>
                        
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="als-tab" data-toggle="tab" href="#content-als" role="tab" onclick="$('#activeTab').val('als-1')">ALS</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pardos_sardos-tab" data-toggle="tab" href="#content-pardos_sardos" role="tab" onclick="$('#activeTab').val('pardos_sardos-1')">PARDOS SARDOS</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pivate_vourcher-tab" data-toggle="tab" href="#content-pivate_vourcher" role="tab" onclick="$('#activeTab').val('pivate_vourcher-1')">Private Voucher</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="enrollment-tab" data-toggle="tab" href="#content-enrollment" role="tab" onclick="$('#activeTab').val('enrollment-1')">Enrollment</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="dropouts-tab" data-toggle="tab" href="#content-dropouts" role="tab" onclick="$('#activeTab').val('dropouts-1')">Drop Outs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="graduates-tab" data-toggle="tab" href="#content-graduates" role="tab" onclick="$('#activeTab').val('graduates-1')">Graduates</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="completers-tab" data-toggle="tab" href="#content-completers" role="tab" onclick="$('#activeTab').val('completers-1')">Completers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="leavers-tab" data-toggle="tab" href="#content-leavers" role="tab" onclick="$('#activeTab').val('leavers-1')">Leavers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="repeaters-tab" data-toggle="tab" href="#content-repeaters" role="tab" onclick="$('#activeTab').val('repeaters-1')">Repeaters</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="overweight-tab" data-toggle="tab" href="#content-overweight" role="tab" onclick="$('#activeTab').val('overweight-1')">Overweight</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="subjects-tab" data-toggle="tab" href="#content-subjects" role="tab" onclick="$('#activeTab').val('subjects-1')">Subjects</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="modules-tab" data-toggle="tab" href="#content-modules" role="tab" onclick="$('#activeTab').val('modules-1')">Modules</a>
                            </li>
                        </ul>

                        <input type="hidden" name="activeTab" id="activeTab" value="">
                        <input type="hidden" name="activeGradeLevel" id="activeGradeLevel" value="">

                        <div class="tab-content" id="myTabContent">
                            <div id="content-als" class="tab-pane fade show active">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-als" role="tablist" aria-orientation="vertical">
                                            <a class="nav-link active" id="v-pills-blp-tab" onclick="activeTab('als-1')" data-toggle="pill" href="#v-pills-blp" role="tab" aria-controls="v-pills-blp" aria-selected="true">BLP</a>
                                            <a class="nav-link" id="v-pills-ae-elem-tab" onclick="activeTab('als-2')" data-toggle="pill" href="#v-pills-ae-elem" role="tab" aria-controls="v-pills-ae-elem" aria-selected="false">A & E - Elementary</a>
                                            <a class="nav-link" id="v-pills-ae-jhs-tab" onclick="activeTab('als-3')" data-toggle="pill" href="#v-pills-ae-jhs" role="tab" aria-controls="v-pills-ae-jhs" aria-selected="false">A & E JHS / SHS</a>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent1">
                                            <div class="tab-pane fade show active" id="v-pills-blp" role="tabpanel" aria-labelledby="v-pills-blp-tab">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Name</th>
                                                            <th scope="col">Male</th>
                                                            <th scope="col">Female</th>
                                                            <th scope="col">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class='als-schools'>
                                                        <?php echo str_replace('dynamicId', 'als', $inputTables); ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-pardos_sardos" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab2" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'pardos_sardos', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent2">
                                            <?php echo str_replace('dynamicId', 'pardos_sardos', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="content-pivate_vourcher" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab3" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'pivate_vourcher', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent3">
                                            <?php echo str_replace('dynamicId', 'pivate_vourcher', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="content-enrollment" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab4" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'enrollment', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent4">
                                            <?php echo str_replace('dynamicId', 'enrollment', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="content-dropouts" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab5" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'dropouts', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent5">
                                            <?php echo str_replace('dynamicId', 'dropouts', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="content-graduates" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab6" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'graduates', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent6">
                                            <?php echo str_replace('dynamicId', 'graduates', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="content-completers" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab7" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'completers', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent7">
                                            <?php echo str_replace('dynamicId', 'completers', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-leavers" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab8" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'leavers', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent8">
                                            <?php echo str_replace('dynamicId', 'leavers', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-repeaters" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab9" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'repeaters', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent9">
                                            <?php echo str_replace('dynamicId', 'repeaters', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-overweight" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab10" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'overweight', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent10">
                                            <?php echo str_replace('dynamicId', 'overweight', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-subjects" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab11" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'subjects', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent11">
                                            <?php echo str_replace('dynamicId', 'subjects', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="content-modules" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills" id="v-pills-tab12" role="tablist" aria-orientation="vertical">
                                            <?php echo str_replace('dynamicId', 'modules', $grade_levels); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <div class="tab-content" id="v-pills-tabContent12">
                                            <?php echo str_replace('dynamicId', 'modules', $grade_level_inputs); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <br>

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

    <script>
        var keys = <?php echo json_encode($attendanceKeys); ?>;
        var attendanceData = <?php echo json_encode($attendanceData); ?>;
        var role = <?php echo $_SESSION['role']?>;

        function activeTab(tab) {
            $('#activeTab').val(tab);
            lockFields();
        }

        function lockFields(){
            var activeTab = $('#activeTab').val().split('-')[0];
            var activeTabGrade = $('#activeTab').val().split('-')[1];
            
            $('table input').each(function() {
                this.value = 0;
                this.disabled = false;
            });
            
            for (var i = 0; i < keys.length; i++) {
                var parts = keys[i].split('-');
                var gender = parts[0];
                var type = parts[1];
                var gradeLevel = parts[2];
                var schoolId = parts[3];
                var inputName = type + '-' + gender + '[' + schoolId + ']';
                var inputBox = document.querySelector('input[name="' + inputName + '"]');
                if (gradeLevel === activeTabGrade && type === activeTab) {
                    if (inputBox) {
                        if (role == 2) {
                            inputBox.disabled = true; 
                        }
                        inputBox.value = attendanceData[keys[i]];
                        updateTotal($(inputBox).closest('tr'));
                    }
                }
            }
            handleAEJHSVisibility();
        }
        
        function updateTotal(row) {
            var male = parseInt(row.find('input')[0].value) || 0;
            var female = parseInt(row.find('input')[1].value) || 0;
            row.find('.total').text(male + female);
        }

        $(document).ready(function(){

            function clearZero(input) {
                if (input.find('input')[0].value == '0') {
                    input.find('input')[0].value = '';
                } else if (input.find('input')[0].value < 1 || input.find('input')[0].value == "") {
                    input.find('input')[0].value = '0';
                }
            }

            $('tbody tr').each(function() {
                updateTotal($(this));
            });

            $('input[type="number"]').on('input', function() {
                updateTotal($(this).closest('tr'));
            });
            
            $('input[type="number"]').on('focus', function() {
                clearZero($(this).closest('tr'));
            });

            $(".nav-item-custom a").click(function(e) {
                e.preventDefault();

                var tabId = $(this).attr("id");
                $(".content-tab").hide();

                $("#content-" + tabId).show(); 

                $(".nav-item-custom").removeClass("active");
                $(this).parent().addClass("active");

                $('#activeTab').val(tabId+"-1");
                lockFields();
            });

            $(".nav-item-custom:first-child a").click();

            // Initialize Bootstrap tabs
            $('#myTab a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
                lockFields();
            });

            // Add this new function to handle A&E JHS/SHS visibility
            function handleAEJHSVisibility() {
                var activeTab = $('#activeTab').val();
                if (activeTab === 'als-3') {
                    // Show only specific schools for A&E JHS/SHS
                    $('.als-schools tr').each(function() {
                        var schoolId = $(this).find('input').first().attr('name').match(/\[(\d+)\]/)[1];
                        if ([18, 19, 20].includes(parseInt(schoolId))) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                } else {
                    // Show all schools for other tabs
                    $('.als-schools tr').show();
                }
            }

            // Call the function when tab changes
            $('.nav-link').on('click', function() {
                setTimeout(handleAEJHSVisibility, 100);
            });

            // Call on page load
            handleAEJHSVisibility();
        });

        function updateActiveTab(tab, gradeLevel) {
            document.getElementById('activeTab').value = tab;
            document.getElementById('activeGradeLevel').value = gradeLevel;
        }
    </script>

</body>

</html>
