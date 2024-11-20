<?php
ini_set('max_input_vars', '3000');

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

// Fetch school years
$currentYear = date('Y');
$nextYear = $currentYear + 1;
$schoolYearSql = "SELECT * FROM school_year WHERE start_year >= $currentYear AND start_year <= $nextYear ORDER BY start_year ASC";
$schoolYearResult = $conn->query($schoolYearSql);
$schoolYears = $schoolYearResult->fetch_all(MYSQLI_ASSOC);

$syrows = "";
foreach ($schoolYears as $sy) {
    $syrows .= "<th scope='col'>S.Y ".$sy['start_year']."-".$sy['end_year']." (M)</th>";
    $syrows .= "<th scope='col'>S.Y ".$sy['start_year']."-".$sy['end_year']." (F)</th>";
}

// Fetch schools
$sql = "SELECT * FROM schools";
$schools = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch grade levels
$sql = "SELECT * FROM grade_level";
$gradeLevels = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch attendance data
$attendanceQuery = "SELECT a.*, sy.start_year, sy.end_year 
                    FROM attendance_summary a
                    JOIN school_year sy ON (a.year BETWEEN sy.start_year AND sy.end_year)
                    WHERE (a.year = sy.start_year AND MONTH(NOW()) >= sy.start_month) 
                       OR (a.year = sy.end_year AND MONTH(NOW()) < sy.end_month)";
$attendanceResult = $conn->query($attendanceQuery);
$attendanceData = [];

while ($row = $attendanceResult->fetch_assoc()) {
    $key = $row['type'] . '-' . $row['grade_level_id'] . '-' . $row['school_id'] . '-' . $row['start_year'] . '-' . $row['end_year'];
    $attendanceData[$key][$row['gender']] = $row['count'];
}

function generateInputTable($type, $gradeLevel, $schools, $schoolYears, $attendanceData) {
    $inputTable = "";
    foreach ($schools as $school) {
        // Special handling for A&E JHS/SHS (grade level 3 in als type)
        $showRow = true;
        if ($type === 'als' && $gradeLevel === 3) {
            $allowedSchoolIds = [18, 19, 20];
            $showRow = in_array($school['id'], $allowedSchoolIds);
        }

        if ($showRow) {
            $inputTable .= "<tr class='school-row' data-school-id='".$school['id']."'><td>".$school["name"]."</td>";
            foreach ($schoolYears as $sy) {
                $key = $type . '-' . $gradeLevel . '-' . $school['id'] . '-' . $sy['start_year'] . '-' . $sy['end_year'];
                $maleValue = isset($attendanceData[$key][1]) ? $attendanceData[$key][1] : '';
                $femaleValue = isset($attendanceData[$key][2]) ? $attendanceData[$key][2] : '';
                
                $inputTable .= "<td><input type='number' class='form-control form-control-sm' name='year-".$sy['start_year']."-".$sy['end_year']."-male[".$school['id']."]' value='$maleValue' readonly></td>";
                $inputTable .= "<td><input type='number' class='form-control form-control-sm' name='year-".$sy['start_year']."-".$sy['end_year']."-female[".$school['id']."]' value='$femaleValue' readonly></td>";
            }
            $inputTable .= "</tr>";
        }
    }
    return $inputTable;
}

function generateGradeLevelTabs($type, $gradeLevels) {
    $tabs = "";
    foreach ($gradeLevels as $index => $level) {
        $active = $index == 0 ? "active" : "";
        $tabs .= "<a class='nav-link $active' id='v-pills-$type-".$level['id']."-tab' onclick=\"activeTab('$type-".$level['id']."')\" data-toggle='pill' href='#v-pills-$type-".$level['id']."' role='tab' aria-controls='v-pills-$type-".$level['id']."' aria-selected='true'>".$level['name']."</a>";
    }
    return $tabs;
}

function generateGradeLevelContent($type, $gradeLevels, $schools, $schoolYears, $attendanceData, $syrows) {
    $content = "";
    foreach ($gradeLevels as $index => $level) {
        $active = $index == 0 ? "show active" : "";
        $content .= "<div class='tab-pane fade $active' id='v-pills-$type-".$level['id']."' role='tabpanel' aria-labelledby='v-pills-$type-".$level['id']."-tab'>
                        <table class='table'>
                            <thead>
                                <tr>
                                    <th scope='col'>Name</th>
                                    $syrows
                                </tr>
                            </thead>
                            <tbody>
                                ".generateInputTable($type, $level['id'], $schools, $schoolYears, $attendanceData)."
                            </tbody>
                        </table>
                    </div>";
    }
    return $content;
}

// Add these export functions after the existing PHP code at the top
function exportCSV($conn, $activeTab) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="attendance_summary.csv"');
    $output = fopen('php://output', 'w');
    
    // Split activeTab to get type and grade level
    list($type, $gradeLevel) = explode('-', $activeTab);
    
    // Get grade level name
    $gradeLevelName = $conn->query("SELECT name FROM grade_level WHERE id = $gradeLevel")->fetch_assoc()['name'];
    
    // CSV headers
    fputcsv($output, ['School Name', 'School Year', 'Male', 'Female', 'Total']);

    // Get school years
    $schoolYears = $conn->query("SELECT * FROM school_year ORDER BY start_year DESC")->fetch_all(MYSQLI_ASSOC);
    
    // Special handling for ALS type
    if ($type === 'als') {
        $alsSchools = [
            1 => [['id' => 1, 'name' => 'BLP']],
            2 => [['id' => 2, 'name' => 'A & E - Elementary']],
            3 => [['id' => 3, 'name' => 'A & E - JHS/SHS']]
        ];
        $schoolsToUse = $alsSchools[$gradeLevel] ?? [];
    } else {
        $schoolsToUse = $schools;
    }

    foreach ($schoolsToUse as $school) {
        foreach ($schoolYears as $sy) {
            $male = $conn->query("SELECT count FROM attendance_summary 
                WHERE school_id = {$school['id']} 
                AND grade_level_id = $gradeLevel 
                AND gender = 1 
                AND type = '$type'
                AND year = {$sy['start_year']}")->fetch_assoc();

            $female = $conn->query("SELECT count FROM attendance_summary 
                WHERE school_id = {$school['id']} 
                AND grade_level_id = $gradeLevel 
                AND gender = 2 
                AND type = '$type'
                AND year = {$sy['start_year']}")->fetch_assoc();

            $maleCount = $male['count'] ?? 0;
            $femaleCount = $female['count'] ?? 0;
            $total = $maleCount + $femaleCount;

            fputcsv($output, [
                $school['name'],
                "S.Y {$sy['start_year']}-{$sy['end_year']}",
                $maleCount,
                $femaleCount,
                $total
            ]);
        }
    }
    
    fclose($output);
    exit;
}

function exportPDF($conn, $activeTab) {
    // Split activeTab to get type and grade level
    list($type, $gradeLevel) = explode('-', $activeTab);
    
    // Get data
    $schoolYears = $conn->query("SELECT * FROM school_year ORDER BY start_year DESC")->fetch_all(MYSQLI_ASSOC);
    $schools = $conn->query("SELECT * FROM schools ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    $gradeLevelName = $conn->query("SELECT name FROM grade_level WHERE id = $gradeLevel")->fetch_assoc()['name'];
    
    // Format the report title
    $reportTitle = $type === 'als' ? 'ALS' : ucfirst($type);
    $reportTitle .= ' - ' . $gradeLevelName . ' Report';
    
    // Start HTML content with updated header
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { margin: 0; color: #4e73df; font-size: 24px; }
            .header p { margin: 5px 0; color: #666; font-size: 14px; }
            .report-title { margin: 20px 0; font-size: 18px; color: #333; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f5f5f5; }
            .date-generated { text-align: right; font-size: 12px; color: #666; margin-bottom: 20px; }
        </style>
        <script>
            window.onafterprint = function() {
                window.location.href = document.referrer;
            };
            
            // Also handle if user cancels the print dialog
            setTimeout(function() {
                if (!document.hidden) {
                    window.location.href = document.referrer;
                }
            }, 1000);
        </script>
    </head>
    <body>
        <div class="header">
            <h1>SMEA - School Management Enrollment Analytics</h1>
            <p>Comparative Report</p>
        </div>
        <div class="date-generated">
            Generated on: ' . date('F d, Y h:i A') . '
        </div>
        <div class="report-title">
            ' . $reportTitle . '
        </div>';

    // Continue with the existing table structure
    $html .= '<table><thead><tr><th>School Name</th>';

    foreach ($schoolYears as $sy) {
        $html .= "<th>S.Y {$sy['start_year']}-{$sy['end_year']} (M)</th>";
        $html .= "<th>S.Y {$sy['start_year']}-{$sy['end_year']} (F)</th>";
    }
    
    $html .= '</tr></thead><tbody>';

    // Special handling for ALS type
    if ($type === 'als') {
        $alsSchools = [
            ['id' => 1, 'name' => 'BLP'],
            ['id' => 2, 'name' => 'A & E - Elementary'],
            ['id' => 3, 'name' => 'A & E - JHS/SHS']
        ];
        $schoolsToUse = $alsSchools;
    } else {
        $schoolsToUse = $schools;
    }

    foreach ($schoolsToUse as $school) {
        $html .= "<tr><td>{$school['name']}</td>";
        
        foreach ($schoolYears as $sy) {
            $male = $conn->query("SELECT count FROM attendance_summary 
                WHERE school_id = {$school['id']} 
                AND grade_level_id = $gradeLevel 
                AND gender = 1 
                AND type = '$type'
                AND year = {$sy['start_year']}")->fetch_assoc();

            $female = $conn->query("SELECT count FROM attendance_summary 
                WHERE school_id = {$school['id']} 
                AND grade_level_id = $gradeLevel 
                AND gender = 2 
                AND type = '$type'
                AND year = {$sy['start_year']}")->fetch_assoc();

            $html .= "<td>" . ($male['count'] ?? '0') . "</td>";
            $html .= "<td>" . ($female['count'] ?? '0') . "</td>";
        }
        
        $html .= "</tr>";
    }
    
    $html .= '</tbody></table></body></html>';

    // Output the HTML and trigger print
    echo $html;
    echo "<script>window.print();</script>";
    exit;
}

// Update the export handling logic
if (isset($_POST['export_csv'])) {
    $activeTab = $_POST['activeTab'] ?? '';
    if (empty($activeTab)) {
        echo "Invalid active tab.";
        exit;
    }
    exportCSV($conn, $activeTab);
}

if (isset($_POST['export_pdf'])) {
    $activeTab = $_POST['activeTab'] ?? '';
    if (empty($activeTab)) {
        echo "Invalid active tab.";
        exit;
    }
    exportPDF($conn, $activeTab);
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
                    </style>

                    <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">Comparative</h1>
                    <p class="mb-4">Data comparison based on School Year</p>
                    
                    <div class="text-right">
                        <form action="" method="post" style="display: inline-block;">
                            <input type="hidden" name="activeTab" id="exportCsvTab">
                            <button type="submit" class="btn btn-info" name="export_csv">Export CSV</button>
                        </form>
                        <form action="" method="post" style="display: inline-block;">
                            <input type="hidden" name="activeTab" id="exportPdfTab">
                            <button type="submit" class="btn btn-warning" name="export_pdf">Export PDF</button>
                        </form>
                    </div>
                    
                    <nav class="navbar navbar-expand-lg navbar-light bg-light navbar-custom">
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav">
                                <li class="nav-item-custom active">
                                    <a class="nav-link" href="#" id="als">ALS Enrollment</a>
                                </li>
                                <li class="nav-item-custom">
                                    <a class="nav-link" href="#" id="enrollment">Enrollment</a>
                                </li>
                                <li class="nav-item-custom">
                                    <a class="nav-link" href="#" id="dropouts">Drop Outs </a>
                                </li>
                                <li class="nav-item-custom">
                                    <a class="nav-link" href="#" id="completers">Completers</a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                    
                    <input type="hidden" name="activeTab" id="activeTab" value="blp">
                    <div id="tabContent">
                        <div id="content-als" class="content-tab">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="nav flex-column nav-pills" id="v-pills-als" role="tablist" aria-orientation="vertical">
                                        <a class="nav-link active" id="v-pills-als-1-tab" onclick="activeTab('als-1')" data-toggle="pill" href="#v-pills-als-1" role="tab" aria-controls="v-pills-als-1" aria-selected="true">BLP</a>
                                        <a class="nav-link" id="v-pills-als-2-tab" onclick="activeTab('als-2')" data-toggle="pill" href="#v-pills-als-2" role="tab" aria-controls="v-pills-als-2" aria-selected="false">A & E - Elementary</a>
                                        <a class="nav-link" id="v-pills-als-3-tab" onclick="activeTab('als-3')" data-toggle="pill" href="#v-pills-als-3" role="tab" aria-controls="v-pills-als-3" aria-selected="false">A&E - JHS</a>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="tab-content" id="v-pills-tabContent1">
                                        <?php
                                        $alsLevels = [
                                            ['id' => 1, 'name' => 'BLP'],
                                            ['id' => 2, 'name' => 'A & E - Elementary'],
                                            ['id' => 3, 'name' => 'A & E JHS / SHS']
                                        ];
                                        echo generateGradeLevelContent('als', $alsLevels, $schools, $schoolYears, $attendanceData, $syrows);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $types = ['pardos_sardos', 'pivate_vourcher', 'enrollment', 'dropouts', 'graduates', 'completers', 'leavers', 'repeaters', 'overweight', 'subjects'];
                        foreach ($types as $type) {
                            echo "<div id='content-$type' class='content-tab'>
                                    <div class='row'>
                                        <div class='col-md-3'>
                                            <div class='nav flex-column nav-pills' id='v-pills-tab-$type' role='tablist' aria-orientation='vertical'>
                                                ".generateGradeLevelTabs($type, $gradeLevels)."
                                            </div>
                                        </div>
                                        <div class='col-md-9'>
                                            <div class='tab-content' id='v-pills-tabContent-$type'>
                                                ".generateGradeLevelContent($type, $gradeLevels, $schools, $schoolYears, $attendanceData, $syrows)."
                                            </div>
                                        </div>
                                    </div>
                                  </div>";
                        }
                        ?>
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
    function activeTab(tab) {
        $('#activeTab').val(tab);
        lockFields();
    }

    function lockFields() {
        $('table input').prop('readonly', true);
    }

    $(document).ready(function(){
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
        lockFields();

        // Add this new function to handle A&E JHS/SHS visibility
        function handleAEJHSVisibility() {
            var activeTab = $('#activeTab').val();
            if (activeTab === 'als-3') {
                // Show only specific schools for A&E JHS/SHS
                $('.school-row').each(function() {
                    var schoolId = $(this).data('school-id');
                    if ([18, 19, 20].includes(schoolId)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                // Show all schools for other tabs
                $('.school-row').show();
            }
        }

        // Call the function when tab changes
        $('.nav-link').on('click', function() {
            setTimeout(handleAEJHSVisibility, 100);
        });

        // Initial call
        handleAEJHSVisibility();

        // Update hidden inputs before form submission
        $('button[name="export_csv"]').click(function() {
            $('#exportCsvTab').val($('#activeTab').val());
        });
        
        $('button[name="export_pdf"]').click(function() {
            $('#exportPdfTab').val($('#activeTab').val());
        });
    });
    </script>

</body>

</html>
