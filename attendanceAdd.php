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
$currentYear = date('Y');

if (isset($_POST['year'])) {
    $year = $_POST['year'];
} else {
    $year = $currentYear;
}

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
                        "UPDATE attendance_summary SET count = '%s', updated_at = CURRENT_TIMESTAMP, last_user_save = '%s' WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s'",
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $current_user_id),
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_male),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year)
                    );
                } else {
                    $query = sprintf(
                        "INSERT INTO attendance_summary (school_id, grade_level_id, gender, type, count, quarter, year, last_user_save, created_at, updated_at) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
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
                        "UPDATE attendance_summary SET count = '%s', updated_at = CURRENT_TIMESTAMP, last_user_save = '%s' WHERE school_id = '%s' AND grade_level_id = '%s' AND gender = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s'",
                        mysqli_real_escape_string($conn, $count),
                        mysqli_real_escape_string($conn, $current_user_id),
                        mysqli_real_escape_string($conn, $school_id),
                        mysqli_real_escape_string($conn, $grade_level_id),
                        mysqli_real_escape_string($conn, $gender_female),
                        mysqli_real_escape_string($conn, $type),
                        mysqli_real_escape_string($conn, $_POST['quarter']),
                        mysqli_real_escape_string($conn, $year)
                    );
                } else {
                    $query = sprintf(
                        "INSERT INTO attendance_summary (school_id, grade_level_id, gender, type, count, quarter, year, last_user_save, created_at, updated_at) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
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
$schools = $conn->query($sql);

$inputTables = "";

if ($schools->num_rows > 0) {
    while($row = $schools->fetch_assoc()) {
        // Get the stored values for this school
        $male_value = 0;
        $female_value = 0;
        
        // Build the key for looking up stored values
        $male_key = "1-dynamicId-1-" . $row['id'];    // 1 for male
        $female_key = "2-dynamicId-1-" . $row['id'];  // 2 for female
        
        if (isset($attendanceData[$male_key])) {
            $male_value = $attendanceData[$male_key];
        }
        if (isset($attendanceData[$female_key])) {
            $female_value = $attendanceData[$female_key];
        }

        $inputTables .= "
            <tr>
                <td>".$row["name"]."</td>
                <td><input type='number' class='form-control form-control-sm' name='dynamicId-male[".$row['id']."]' value='".$male_value."'></td>
                <td><input type='number' class='form-control form-control-sm' name='dynamicId-female[".$row['id']."]' value='".$female_value."'></td>
                <td class='total'>".($male_value + $female_value)."</td>
            </tr>
        ";
    }
}

$attendanceQuery = "SELECT a.*, 
                          u.id as editor_id,
                          u.username as editor_name,
                          a.created_at,
                          a.updated_at,
                          a.last_user_save
                   FROM attendance_summary a 
                   LEFT JOIN users u ON a.last_user_save = u.id 
                   WHERE a.quarter = ? AND a.year = ?";
$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("ii", $selectedQuarter, $year);
$stmt->execute();
$result = $stmt->get_result();

$attendanceData = [];
$attendanceKeys = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['gender'] . '-' . $row['type'] . '-' . $row['grade_level_id'] . '-' . $row['school_id'];
    $attendanceData[$key] = $row['count'];
    $attendanceData[$key . '_editor'] = $row['editor_id'];
    $attendanceData[$key . '_editor_name'] = $row['editor_name'];
    $attendanceData[$key . '_last_update'] = $row['updated_at'] ?? $row['created_at'];
    $attendanceKeys[] = $key;
}

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

$editRequestsQuery = "SELECT school_id, type, grade_level, gender, status 
                     FROM edit_requests 
                     WHERE requested_by = {$_SESSION['user_id']} 
                     AND status IN ('pending', 'approved')";
$editRequestsResult = $conn->query($editRequestsQuery);
$editPermissions = [];

while ($row = $editRequestsResult->fetch_assoc()) {
    $key = "{$row['type']}-{$row['gender']}-{$row['grade_level']}-{$row['school_id']}";
    $editPermissions[$key] = $row['status'];
}

// Get the last user who saved data for the current quarter and year
$lastUserQuery = "SELECT u.first_name, u.last_name, s.name as school_name, s.address as school_address
                 FROM attendance_summary a
                 JOIN users u ON a.last_user_save = u.id
                 LEFT JOIN schools s ON s.id = u.school_id 
                 WHERE a.quarter = ? AND a.year = ?
                 ORDER BY a.updated_at DESC
                 LIMIT 1";
$stmt = $conn->prepare($lastUserQuery);
$stmt->bind_param("ii", $selectedQuarter, $year);
$stmt->execute();
$lastUserResult = $stmt->get_result();
$lastUserRow = $lastUserResult->fetch_assoc();
$lastUserSave = $lastUserRow ? $lastUserRow['last_name'] . ', ' . $lastUserRow['first_name'] . ' (' . ($lastUserRow['school_name'] ?? 'Admin'). ')' : 'No entries yet';

// Fetch school years for the dropdown
$schoolYearQuery = "SELECT * FROM school_year ORDER BY start_year DESC";
$schoolYears = $conn->query($schoolYearQuery)->fetch_all(MYSQLI_ASSOC);

// Update the year selection logic
$year = isset($_GET['year']) ? $_GET['year'] : (isset($_POST['year']) ? $_POST['year'] : $currentYear);

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

                        .approved-edit {
                            background-color: #e8f5e9 !important;
                            border-color: #4caf50 !important;
                        }
                        
                        .pending-edit {
                            background-color: #fff3e0 !important;
                            border-color: #ff9800 !important;
                        }
                        
                        .edit-status-tooltip {
                            position: relative;
                        }
                        
                        .edit-status-tooltip:after {
                            content: attr(data-status);
                            position: absolute;
                            right: -5px;
                            top: -5px;
                            font-size: 10px;
                            padding: 2px 5px;
                            border-radius: 3px;
                            color: white;
                        }
                        
                        .edit-status-tooltip.approved-edit:after {
                            background-color: #4caf50;
                            content: "Approved";
                        }
                        
                        .edit-status-tooltip.pending-edit:after {
                            background-color: #ff9800;
                            content: "Pending";
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
                                <label for="year">Select Year:</label>
                                <select id="year" name="year">
                                    <?php foreach ($schoolYears as $sy): ?>
                                        <option value="<?php echo $sy['end_year']; ?>" <?php echo $year == $sy['end_year'] ? 'selected' : ''; ?>>
                                            SY <?php echo $sy['start_year']."-".$sy['end_year']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-success" name="filter">Select</button>
                            </div>
                            <div class="col-md-6 text-right">
                                Last Edited By: <?php echo $lastUserSave; ?>
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
                            <!-- <li class="nav-item">
                                <a class="nav-link" id="modules-tab" data-toggle="tab" href="#content-modules" role="tab" onclick="$('#activeTab').val('modules-1')">Modules</a>
                            </li> -->
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
                            
                            <!-- <div id="content-modules" class="tab-pane fade">
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
                            </div> -->
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

    <!-- Add this new modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1" role="dialog" aria-labelledby="editRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRequestModalLabel">Request Edit Permission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>This field is currently locked. Would you like to request permission to edit it?</p>
                    <form id="editRequestForm">
                        <input type="hidden" id="requestSchoolId" name="school_id">
                        <input type="hidden" id="requestType" name="type">
                        <input type="hidden" id="requestGradeLevel" name="grade_level">
                        <input type="hidden" id="requestGender" name="gender">
                        <div class="form-group">
                            <label for="requestReason">Reason for Edit:</label>
                            <textarea class="form-control" id="requestReason" name="reason" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-body" id="editRequestStatus" style="display: none;">
                    <div class="alert" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitEditRequest">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var keys = <?php echo json_encode($attendanceKeys); ?>;
        var attendanceData = <?php echo json_encode($attendanceData); ?>;
        var role = <?php echo $_SESSION['role']?>;
        var editPermissions = <?php echo json_encode($editPermissions); ?>;
        var currentUserId = <?php echo $_SESSION['user_id']; ?>;

        function activeTab(tab) {
            $('#activeTab').val(tab);
            lockFields();
        }

        function handleAEJHSVisibility() {
            var activeTab = $('#activeTab').val();
            if (activeTab === 'als-3') {
                $('.als-schools tr').each(function() {
                    var schoolId = $(this).find('input').first().attr('name').match(/\[(\d+)\]/)[1];
                    if ([18, 19, 20].includes(parseInt(schoolId))) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('.als-schools tr').show();
            }
        }

        function lockFields() {
            var activeTab = $('#activeTab').val().split('-')[0];
            var activeTabGrade = $('#activeTab').val().split('-')[1];
            
            // First reset all inputs
            $('table input').each(function() {
                var inputName = $(this).attr('name');
                if (inputName) {
                    $(this).val(0);
                    $(this).removeClass('approved-edit pending-edit');
                }
            });
            
            // Then populate with actual data
            for (var i = 0; i < keys.length; i++) {
                var parts = keys[i].split('-');
                var gender = parts[0];  // 1 for male, 2 for female
                var type = parts[1];    // als, enrollment, etc.
                var gradeLevel = parts[2];
                var schoolId = parts[3];
                
                // Convert gender number to string
                var genderStr = (gender === '1') ? 'male' : 'female';
                
                // Construct input name
                var inputName = type + '-' + genderStr + '[' + schoolId + ']';
                var inputBox = $('input[name="' + inputName + '"]');
                
                if (gradeLevel === activeTabGrade && type === activeTab && inputBox.length) {
                    // Set the value
                    inputBox.val(attendanceData[keys[i]] || 0);
                    
                    // Check permissions
                    var lastEditor = attendanceData[keys[i] + '_editor'];
                    if (lastEditor === currentUserId) {
                        inputBox.prop('disabled', false);
                    } else {
                        var permissionKey = type + '-' + gender + '-' + gradeLevel + '-' + schoolId;
                        var permission = editPermissions[permissionKey];
                        
                        if (permission === 'approved') {
                            inputBox.prop('disabled', false);
                            inputBox.addClass('approved-edit');
                        } else if (permission === 'pending') {
                            inputBox.prop('disabled', true);
                            inputBox.addClass('pending-edit');
                        } else {
                            inputBox.prop('disabled', true);
                        }
                    }
                    
                    // Update the total
                    updateTotal(inputBox.closest('tr'));
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

            // Initialize the double-click handler
            $(document).on('dblclick', 'input[type="number"]', function() {
                if ($(this).prop('disabled')) {
                    const $input = $(this);
                    const inputName = $input.attr('name');
                    const matches = inputName.match(/([^-]+)-([^[]+)\[(\d+)\]/);
                    
                    if (matches) {
                        const type = matches[1];
                        const gender = matches[2];
                        const schoolId = matches[3];
                        const gradeLevel = $('#activeTab').val().split('-')[1];

                        $('#requestSchoolId').val(schoolId);
                        $('#requestType').val(type);
                        $('#requestGradeLevel').val(gradeLevel);
                        $('#requestGender').val(gender);
                        
                        $('#editRequestModal').modal('show');
                    }
                }
            });

            // Handle edit request submission
            $('#submitEditRequest').on('click', function() {
                const formData = {
                    school_id: $('#requestSchoolId').val(),
                    type: $('#requestType').val(),
                    grade_level: $('#requestGradeLevel').val(),
                    gender: $('#requestGender').val(),
                    reason: $('#requestReason').val()
                };

                $.ajax({
                    url: 'requestEdit.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        const result = JSON.parse(response);
                        $('#editRequestForm').hide();
                        $('#editRequestStatus').show();
                        $('#editRequestStatus .alert')
                            .removeClass('alert-danger alert-success')
                            .addClass('alert-success')
                            .text(result.message);
                        
                        // Hide submit button after successful submission
                        $('#submitEditRequest').hide();
                    },
                    error: function() {
                        $('#editRequestStatus').show();
                        $('#editRequestStatus .alert')
                            .removeClass('alert-danger alert-success')
                            .addClass('alert-danger')
                            .text('An error occurred. Please try again.');
                    }
                });
            });

            // Reset modal when closed
            $('#editRequestModal').on('hidden.bs.modal', function () {
                $('#editRequestForm').show();
                $('#editRequestStatus').hide();
                $('#submitEditRequest').show();
                $('#requestReason').val('');
            });

            // Set the default active tab to als-1
            $('#activeTab').val('als-1');
            $('#v-pills-blp-tab').addClass('active');
            $('#v-pills-blp').addClass('show active');

            // Initialize the fields based on the default active tab
            lockFields();
            handleAEJHSVisibility();
        });

        function updateActiveTab(tab, gradeLevel) {
            console.log(tab);
            document.getElementById('activeTab').value = tab;
            document.getElementById('activeGradeLevel').value = gradeLevel;
        }

        // Update the checkNotifications function
        function checkNotifications() {
            $.ajax({
                url: 'checkNotifications.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.count !== undefined) {  // Check if response is valid
                        $('#alertsDropdown .badge-counter').text(data.count > 0 ? data.count : '');
                        let dropdown = $('#alertsDropdown .dropdown-list');
                        dropdown.empty();
                        dropdown.append('<h6 class="dropdown-header">Alerts Center</h6>');
                        if (data.requests && data.requests.length > 0) {
                            data.requests.forEach(function(request) {
                                dropdown.append('<a class="dropdown-item text-center small text-gray-500" href="adminEditRequests.php">Edit request from ' + request.user_name + ' for ' + request.type + '</a>');
                            });
                        } else {
                            dropdown.append('<div class="dropdown-item text-center small text-gray-500">No pending requests</div>');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    if (xhr.responseText) {
                        console.error("Response:", xhr.responseText);
                    }
                }
            });
        }
    </script>

</body>

</html>
