<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

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

// Fetch quality assessment data
$qualityQuery = "SELECT qa.*, sy.start_year, sy.end_year 
                 FROM quality_assessment qa
                 JOIN school_year sy ON (qa.year BETWEEN sy.start_year AND sy.end_year)
                 WHERE (qa.year = sy.start_year AND MONTH(NOW()) >= sy.start_month) 
                    OR (qa.year = sy.end_year AND MONTH(NOW()) < sy.end_month)";
$qualityResult = $conn->query($qualityQuery);
$qualityData = [];

while ($row = $qualityResult->fetch_assoc()) {
    $key = $row['type'] . '-' . $row['grade_level'] . '-' . $row['school_id'] . '-' . $row['start_year'] . '-' . $row['end_year'];
    $qualityData[$key][$row['gender']] = $row['count'];
}

function generateInputTable($type, $gradeLevel, $schools, $schoolYears, $qualityData) {
    $inputTable = "";
    foreach ($schools as $school) {
        // Special handling for A&E JHS/SHS
        $showRow = true;
        if ($type === 'als' && $gradeLevel === 3) {
            $allowedSchoolIds = [18, 19, 20];
            $showRow = in_array($school['id'], $allowedSchoolIds);
        }

        if ($showRow) {
            $inputTable .= "<tr class='school-row' data-school-id='".$school['id']."'><td>".$school["name"]."</td>";
            foreach ($schoolYears as $sy) {
                $key = $type . '-' . $gradeLevel . '-' . $school['id'] . '-' . $sy['start_year'] . '-' . $sy['end_year'];
                $maleValue = isset($qualityData[$key][1]) ? $qualityData[$key][1] : '';
                $femaleValue = isset($qualityData[$key][2]) ? $qualityData[$key][2] : '';
                
                $inputTable .= "<td><input type='number' class='form-control form-control-sm' value='$maleValue' readonly></td>";
                $inputTable .= "<td><input type='number' class='form-control form-control-sm' value='$femaleValue' readonly></td>";
            }
            $inputTable .= "</tr>";
        }
    }
    return $inputTable;
}

function generateGradeLevelContent($type, $gradeLevels, $schools, $schoolYears, $qualityData, $syrows) {
    $content = "";
    foreach ($gradeLevels as $index => $level) {
        $active = $index == 0 ? "show active" : "";
        $content .= "<div class='tab-pane fade $active' id='v-pills-$type-".$level['id']."' role='tabpanel'>
                        <table class='table'>
                            <thead>
                                <tr>
                                    <th scope='col'>Name</th>
                                    $syrows
                                </tr>
                            </thead>
                            <tbody>
                                ".generateInputTable($type, $level['id'], $schools, $schoolYears, $qualityData)."
                            </tbody>
                        </table>
                    </div>";
    }
    return $content;
}

// Export functions
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="quality_assessment_summary.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['School Year', 'Grade Level', 'School Name', 'Male', 'Female', 'Total']);

    foreach ($schoolYears as $sy) {
        foreach ($gradeLevels as $level) {
            foreach ($schools as $school) {
                $key = 'als-' . $level['id'] . '-' . $school['id'] . '-' . $sy['start_year'] . '-' . $sy['end_year'];
                $male = $qualityData[$key][1] ?? 0;
                $female = $qualityData[$key][2] ?? 0;
                $total = $male + $female;
                
                fputcsv($output, [
                    "S.Y " . $sy['start_year'] . "-" . $sy['end_year'],
                    $level['name'],
                    $school['name'],
                    $male,
                    $female,
                    $total
                ]);
            }
        }
    }
    fclose($output);
    exit;
}
?>

<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once "navbar.php"?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Quality Assessment Comparative</h1>
                    <p class="mb-4">Compare quality assessment data across schools</p>

                    <form method="post">
                        <div class="text-right mb-3">
                            <button type="submit" class="btn btn-info" name="export_csv">Export CSV</button>
                        </div>

                        <nav class="navbar navbar-expand-lg navbar-light bg-light navbar-custom">
                            <div class="collapse navbar-collapse" id="navbarNav">
                                <ul class="navbar-nav">
                                    <li class="nav-item-custom active">
                                        <a class="nav-link" href="#" id="als">ALS</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="eng">English Reading</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="fil">Filipino Reading</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>

                        <div id="tabContent">
                            <!-- ALS Content -->
                            <div id="content-als" class="content-tab">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="nav flex-column nav-pills">
                                            <?php
                                            $alsLevels = [
                                                ['id' => 1, 'name' => 'BLP'],
                                                ['id' => 2, 'name' => 'A & E - Elementary'],
                                                ['id' => 3, 'name' => 'A & E JHS / SHS']
                                            ];
                                            foreach ($alsLevels as $index => $level) {
                                                $active = $index == 0 ? 'active' : '';
                                                echo "<a class='nav-link $active' data-toggle='pill' href='#v-pills-als-{$level['id']}'>{$level['name']}</a>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="tab-content">
                                            <?php echo generateGradeLevelContent('als', $alsLevels, $schools, $schoolYears, $qualityData, $syrows); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reading Assessment Contents -->
                            <?php
                            $readingTypes = [
                                'eng' => ['frustration', 'instructional', 'independent'],
                                'fil' => ['frustration', 'instructional', 'independent']
                            ];

                            foreach ($readingTypes as $type => $levels) {
                                echo "<div id='content-$type' class='content-tab' style='display:none;'>
                                        <div class='row'>
                                            <div class='col-md-3'>
                                                <div class='nav flex-column nav-pills'>";
                                foreach ($levels as $index => $level) {
                                    $active = $index == 0 ? 'active' : '';
                                    echo "<a class='nav-link $active' data-toggle='pill' href='#v-pills-$type-$level'>".ucfirst($level)."</a>";
                                }
                                echo "</div>
                                    </div>
                                    <div class='col-md-9'>
                                        <div class='tab-content'>";
                                foreach ($levels as $index => $level) {
                                    $active = $index == 0 ? 'show active' : '';
                                    echo "<div class='tab-pane fade $active' id='v-pills-$type-$level'>
                                            <table class='table'>
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        $syrows
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ".generateInputTable("$type-$level", 1, $schools, $schoolYears, $qualityData)."
                                                </tbody>
                                            </table>
                                          </div>";
                                }
                                echo "</div>
                                    </div>
                                  </div>
                                </div>";
                            }
                            ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once "footer.php"; ?>
        </div>
    </div>

    <?php include_once "logout-modal.php"?>
    <?php include_once "footer.php"?>

    <script>
    $(document).ready(function(){
        $(".nav-item-custom a").click(function(e) {
            e.preventDefault();
            var tabId = $(this).attr("id");
            $(".content-tab").hide();
            $("#content-" + tabId).show();
            $(".nav-item-custom").removeClass("active");
            $(this).parent().addClass("active");
            handleAEJHSVisibility();
        });

        function handleAEJHSVisibility() {
            var activeTab = $('.nav-link.active').attr('href');
            if (activeTab === '#v-pills-als-3') {
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

        $('.nav-pills .nav-link').on('click', function() {
            setTimeout(handleAEJHSVisibility, 100);
        });

        $(".nav-item-custom:first-child a").click();
        
        handleAEJHSVisibility();
    });
    </script>
</body>
</html>
