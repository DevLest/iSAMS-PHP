<?php
ob_start();
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

// Get schools for input tables
$schoolQuery = "SELECT * FROM schools ORDER BY id";
$schoolResult = $conn->query($schoolQuery);
$inputTables = "";

if ($schoolResult->num_rows > 0) {
    while($row = $schoolResult->fetch_assoc()) {
        $inputTables .= '
            <tr>
                <td>'.$row["name"].'</td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="dynamicId-male['.$row["id"].']" value="0"></td>
                <td><input type="number" min="0" class="form-control form-control-sm" name="dynamicId-female['.$row["id"].']" value="0"></td>
                <td class="total">0</td>
            </tr>';
    }
}

// Get grade levels
$grade_levels = "";
$grade_level_inputs = "";
$sql = "SELECT * FROM grade_level ORDER BY id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Build grade level navigation pills
        $grade_levels .= '
            <a class="nav-link '.($row["id"] == 1 ? 'active' : '').'" 
               id="v-pills-dynamicId-'.$row["id"].'-tab" 
               data-toggle="pill" 
               href="#v-pills-dynamicId-'.$row["id"].'" 
               role="tab" 
               onclick="activeTab(\'dynamicId-'.$row["id"].'\')">'.$row["name"].'</a>';

        // Build grade level content areas
        $grade_level_inputs .= '
            <div class="tab-pane fade '.($row["id"] == 1 ? 'show active' : '').'" 
                 id="v-pills-dynamicId-'.$row["id"].'" 
                 role="tabpanel">
                <table class="table">
                    <thead>
                        <tr>
                            <th>School Name</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.str_replace('dynamicId', 'dynamicId-'.$row["id"], $inputTables).'
                    </tbody>
                </table>
            </div>';
    }
}

$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);
$year = date('Y');

if (isset($_POST['quarter'])) {
    $selectedQuarter = $_POST['quarter'];
} else {
    $selectedQuarter = $currentQuarter;
}

// Save functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $stmt = $conn->prepare("INSERT INTO quality_assessment (school_id, grade_level, gender, type, count, quarter, year, last_user_save) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $gender_male = 1;
    $gender_female = 2;
    $current_user_id = $_SESSION['user_id'];

    list($type, $grade_level_id) = explode('-', $_POST['activeTab']);

    if (isset($_POST[$type.'-male']) || isset($_POST[$type.'-female'])) {
        foreach ($_POST[$type.'-male'] as $school_id => $count) {
            $count = (int) $count;
            if ($count > 0) {
                $query = "SELECT * FROM quality_assessment 
                         WHERE school_id = '$school_id' 
                         AND grade_level = '$grade_level_id' 
                         AND gender = '$gender_male' 
                         AND type = '$type' 
                         AND quarter = '{$_POST['quarter']}' 
                         AND year = '$year'";
                
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                    $query = "UPDATE quality_assessment 
                             SET count = '$count', 
                                 last_user_save = '$current_user_id' 
                             WHERE school_id = '$school_id' 
                             AND grade_level = '$grade_level_id' 
                             AND gender = '$gender_male' 
                             AND type = '$type' 
                             AND quarter = '{$_POST['quarter']}' 
                             AND year = '$year'";
                } else {
                    $query = "INSERT INTO quality_assessment 
                             (school_id, grade_level, gender, type, count, quarter, year, last_user_save) 
                             VALUES 
                             ('$school_id', '$grade_level_id', '$gender_male', '$type', '$count', '{$_POST['quarter']}', '$year', '$current_user_id')";
                }
                $conn->query($query);
            }
        }

        // Similar logic for female data
        foreach ($_POST[$type.'-female'] as $school_id => $count) {
            $count = (int) $count;
            if ($count > 0) {
                // Same logic as above but with $gender_female
            }
        }
    }
}

// Get existing quality assessment data
$qualityQuery = "SELECT * FROM quality_assessment WHERE quarter = $selectedQuarter AND year = $year";
$qualityResult = $conn->query($qualityQuery);
$qualityData = [];
while($row = $qualityResult->fetch_assoc()) {
    $keyId = ($row['gender'] == 1 ? 'male' : 'female').'-'.$row['type'].'-'.$row['grade_level'].'-'.$row['school_id'];
    $qualityData[$keyId] = $row['count'];
}
$qualityKeys = array_keys($qualityData);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Assessment Data Entry</title>
    <!-- Include your CSS and other head elements -->
    <style>
        /* Add to your existing styles */
        .table input.form-control-sm {
            width: 80px;
            margin: 0 auto;
            text-align: center;
        }
        
        .table th {
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .card-header h4 {
            margin-bottom: 0;
        }
        
        .font-weight-bold td {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once "navbar.php"; ?>
                
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Quality Assessment Data Entry</h1>
                    <p class="mb-4">Enter quality assessment data for analysis and comparison.</p>

                    <form action="qualityAdd.php" method="post">
                        <!-- Quarter selection and buttons -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="quarter">Select Quarter:</label>
                                <select id="quarter" name="quarter">
                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($selectedQuarter == $i) ? 'selected' : ''; ?>><?php echo $i; ?><?php 
                                            if($i == 1) echo 'st';
                                            else if($i == 2) echo 'nd'; 
                                            else if($i == 3) echo 'rd';
                                            else echo 'th';
                                        ?> Quarter</option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="btn btn-success" name="filter">Select</button>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-primary" name="save">Save</button>
                                <button type="submit" class="btn btn-info" name="export_csv">Export CSV</button>
                                <button type="submit" class="btn btn-warning" name="export_pdf">Export PDF</button>
                            </div>
                        </div>

                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="asl-tab" data-toggle="tab" href="#asl" role="tab">ASL</a>
                            </li>
                            <!-- English Reading tabs -->
                            <li class="nav-item">
                                <a class="nav-link" id="eng-frustration-tab" data-toggle="tab" href="#eng-frustration" role="tab">English Reading Frustration</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="eng-instructional-tab" data-toggle="tab" href="#eng-instructional" role="tab">English Reading Instructional</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="eng-independent-tab" data-toggle="tab" href="#eng-independent" role="tab">English Reading Independent</a>
                            </li>
                            <!-- Filipino Reading tabs -->
                            <li class="nav-item">
                                <a class="nav-link" id="fil-frustration-tab" data-toggle="tab" href="#fil-frustration" role="tab">Filipino Reading Frustration</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="fil-instructional-tab" data-toggle="tab" href="#fil-instructional" role="tab">Filipino Reading Instructional</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="fil-independent-tab" data-toggle="tab" href="#fil-independent" role="tab">Filipino Reading Independent</a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="myTabContent">
                            <!-- ASL Tab Content -->
                            <div class="tab-pane fade show active" id="asl" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4>Total Number of Learning Sessions Conducted (ALS)</h4>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th rowspan="2">No.</th>
                                                            <th rowspan="2">NAME OF SCHOOL</th>
                                                            <th colspan="1" class="text-center">BLP</th>
                                                            <th colspan="1" class="text-center">A & E – ELEMENTARY</th>
                                                            <th colspan="1" class="text-center">A & E – JHS</th>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-center">TOTAL</th>
                                                            <th class="text-center">TOTAL</th>
                                                            <th class="text-center">TOTAL</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="5"><strong>ELEMENTARY</strong></td>
                                                        </tr>
                                                        <?php
                                                        $schoolResult->data_seek(0);
                                                        $counter = 1;
                                                        while($row = $schoolResult->fetch_assoc()) {
                                                            echo "<tr>";
                                                            echo "<td>".$counter."</td>";
                                                            echo "<td>".$row['name']."</td>";
                                                            echo "<td><input type='number' class='form-control form-control-sm' name='asl-blp[{$row['id']}]' value='0'></td>";
                                                            echo "<td><input type='number' class='form-control form-control-sm' name='asl-elementary[{$row['id']}]' value='0'></td>";
                                                            echo "<td><input type='number' class='form-control form-control-sm' name='asl-jhs[{$row['id']}]' value='0'></td>";
                                                            echo "</tr>";
                                                            $counter++;
                                                        }
                                                        ?>
                                                        <tr class="font-weight-bold">
                                                            <td colspan="2" class="text-right">TOTAL</td>
                                                            <td class="blp-total">0</td>
                                                            <td class="elementary-total">0</td>
                                                            <td class="jhs-total">0</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- English Reading Tabs Content -->
                            <?php
                            $readingLevels = ['frustration', 'instructional', 'independent'];
                            foreach($readingLevels as $level) {
                                echo "<div class='tab-pane fade' id='eng-{$level}' role='tabpanel'>";
                                echo "<div class='row'>";
                                echo "<div class='col-md-3'>";
                                echo "<div class='nav flex-column nav-pills' role='tablist'>";
                                echo str_replace('dynamicId', "eng-{$level}", $grade_levels);
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='col-md-9'>";
                                echo "<div class='tab-content'>";
                                echo "<table class='table'>";
                                echo "<thead><tr><th>School Name</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>";
                                echo "<tbody>";
                                
                                $schoolResult->data_seek(0);
                                while($row = $schoolResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>".$row['name']."</td>";
                                    echo "<td><input type='number' class='form-control' name='eng-{$level}-male[{$row['id']}]' value='0'></td>";
                                    echo "<td><input type='number' class='form-control' name='eng-{$level}-female[{$row['id']}]' value='0'></td>";
                                    echo "<td class='total'>0</td>";
                                    echo "</tr>";
                                }
                                
                                echo "</tbody></table>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                            }
                            ?>

                            <!-- Filipino Reading Tabs Content -->
                            <?php
                            foreach($readingLevels as $level) {
                                echo "<div class='tab-pane fade' id='fil-{$level}' role='tabpanel'>";
                                echo "<div class='row'>";
                                echo "<div class='col-md-3'>";
                                echo "<div class='nav flex-column nav-pills' role='tablist'>";
                                echo str_replace('dynamicId', "fil-{$level}", $grade_levels);
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='col-md-9'>";
                                echo "<div class='tab-content'>";
                                echo "<table class='table'>";
                                echo "<thead><tr><th>School Name</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>";
                                echo "<tbody>";
                                
                                $schoolResult->data_seek(0);
                                while($row = $schoolResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>".$row['name']."</td>";
                                    echo "<td><input type='number' class='form-control' name='fil-{$level}-male[{$row['id']}]' value='0'></td>";
                                    echo "<td><input type='number' class='form-control' name='fil-{$level}-female[{$row['id']}]' value='0'></td>";
                                    echo "<td class='total'>0</td>";
                                    echo "</tr>";
                                }
                                
                                echo "</tbody></table>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once "footer.php"; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        $(document).ready(function() {
            // Update totals when input changes
            $('input[type="number"]').on('change', function() {
                updateTotal($(this).closest('tr'));
            });

            // Load existing data
            var qualityData = <?php echo json_encode($qualityData); ?>;
            var qualityKeys = <?php echo json_encode($qualityKeys); ?>;
            
            // Function to update totals
            function updateTotal(row) {
                var male = parseInt(row.find('input[name*="male"]').val()) || 0;
                var female = parseInt(row.find('input[name*="female"]').val()) || 0;
                row.find('.total').text(male + female);
            }

            // Initialize with existing data
            for(var key in qualityData) {
                var input = $('input[name="' + key + '"]');
                if(input.length) {
                    input.val(qualityData[key]);
                    updateTotal(input.closest('tr'));
                }
            }

            // Add this to your existing script section
            function updateALSTotals() {
                let blpTotal = 0;
                let elementaryTotal = 0;
                let jhsTotal = 0;

                // Calculate BLP total
                $('input[name^="asl-blp"]').each(function() {
                    blpTotal += parseInt($(this).val()) || 0;
                });

                // Calculate Elementary total
                $('input[name^="asl-elementary"]').each(function() {
                    elementaryTotal += parseInt($(this).val()) || 0;
                });

                // Calculate JHS total
                $('input[name^="asl-jhs"]').each(function() {
                    jhsTotal += parseInt($(this).val()) || 0;
                });

                // Update totals in the table
                $('.blp-total').text(blpTotal);
                $('.elementary-total').text(elementaryTotal);
                $('.jhs-total').text(jhsTotal);
            }

            // Add this to your document ready function
            $('input[name^="asl"]').on('input', function() {
                updateALSTotals();
            });

            // Initial calculation
            updateALSTotals();
        });
    </script>
</body>
</html>
