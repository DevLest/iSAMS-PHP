<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

// Get current quarter and year
$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);
$selectedQuarter = isset($_GET['quarter']) ? $_GET['quarter'] : (isset($_POST['quarter']) ? $_POST['quarter'] : $currentQuarter);

// Fetch school years
$currentYear = date('Y');
$nextYear = $currentYear + 1;
$schoolYearQuery = "SELECT * FROM school_year 
                   WHERE (start_year = $currentYear OR start_year = $currentYear - 1)
                   ORDER BY start_year DESC 
                   LIMIT 2";
$schoolYears = $conn->query($schoolYearQuery)->fetch_all(MYSQLI_ASSOC);

// Get schools
$schoolQuery = "SELECT * FROM schools WHERE id <= 17 ORDER BY name";
$schools = $conn->query($schoolQuery)->fetch_all(MYSQLI_ASSOC);

// Get grade levels
$gradeLevelQuery = "SELECT * FROM grade_level ORDER BY id";
$gradeLevels = $conn->query($gradeLevelQuery)->fetch_all(MYSQLI_ASSOC);

// Generate table headers for school years
function generateSchoolYearHeaders($schoolYears) {
    $headers = '<th>School Name</th>';
    foreach ($schoolYears as $sy) {
        $startYear = $sy['start_year'];
        $endYear = $sy['end_year'];
        $headers .= "<th>SY {$startYear}-{$endYear} (M)</th>";
        $headers .= "<th>SY {$startYear}-{$endYear} (F)</th>";
    }
    return $headers;
}

// Generate table content
function generateTableHTML($conn, $type, $quarter, $schools, $schoolYears, $gradeLevel) {
    $html = '<table class="table table-bordered">
             <thead><tr>' . generateSchoolYearHeaders($schoolYears) . '</tr></thead>
             <tbody>';
    
    foreach ($schools as $school) {
        $html .= "<tr><td>{$school['name']}</td>";
        
        foreach ($schoolYears as $sy) {
            $query = "SELECT gender, SUM(count) as total 
                     FROM quality_assessment 
                     WHERE school_id = ? AND type = ? 
                     AND quarter = ? AND year = ? AND grade_level = ?
                     GROUP BY gender";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isiii', $school['id'], $type, $quarter, $sy['start_year'], $gradeLevel);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $male = $female = 0;
            while ($row = $result->fetch_assoc()) {
                if ($row['gender'] == 1) $male = $row['total'];
                if ($row['gender'] == 2) $female = $row['total'];
            }
            
            $html .= "<td>$male</td><td>$female</td>";
        }
        $html .= "</tr>";
    }
    
    $html .= '</tbody></table>';
    return $html;
}

// Handle exports
if (isset($_POST['export_csv'])) {
    $type = $_POST['activeTab'];
    $gradeLevel = $_POST['activeGrade'];
    exportCSV($conn, $type, $selectedQuarter, $schools, $schoolYears, $gradeLevel);
}

if (isset($_POST['export_pdf'])) {
    $type = $_POST['activeTab'];
    $gradeLevel = $_POST['activeGrade'];
    exportPDF($conn, $type, $selectedQuarter, $schools, $schoolYears, $gradeLevel);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quality Assessment Summary</title>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once "navbar.php"; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Quality Assessment Summary</h1>
                    <p class="mb-4">View and export quality assessment data</p>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="quarter">Select Quarter:</label>
                            <select id="quarter" name="quarter" class="form-control d-inline-block w-auto mr-2">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($selectedQuarter == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?><?php echo ($i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th'))); ?> Quarter
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 text-right">
                            <form action="" method="post" style="display: inline-block;">
                                <input type="hidden" name="activeTab" id="exportCsvTab" value="als">
                                <input type="hidden" name="activeGrade" id="exportCsvGrade" value="1">
                                <button type="submit" class="btn btn-info" name="export_csv">
                                    <i class="fas fa-file-csv mr-2"></i>Export CSV
                                </button>
                            </form>
                            <form action="" method="post" style="display: inline-block;">
                                <input type="hidden" name="activeTab" id="exportPdfTab" value="als">
                                <input type="hidden" name="activeGrade" id="exportPdfGrade" value="1">
                                <button type="submit" class="btn btn-warning" name="export_pdf">
                                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="pill" href="#als">ALS</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#eng-frustration">English Reading Frustration</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#eng-instructional">English Reading Instructional</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#eng-independent">English Reading Independent</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#fil-frustration">Filipino Reading Frustration</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#fil-instructional">Filipino Reading Instructional</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#fil-independent">Filipino Reading Independent</a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content">
                                <?php
                                $types = ['als', 'eng-frustration', 'eng-instructional', 'eng-independent', 
                                         'fil-frustration', 'fil-instructional', 'fil-independent'];
                                foreach ($types as $type):
                                ?>
                                <div class="tab-pane fade <?php echo $type === 'als' ? 'show active' : ''; ?>" id="<?php echo $type; ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills">
                                                <?php foreach ($gradeLevels as $level): ?>
                                                <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>"
                                                   data-toggle="pill" 
                                                   href="#<?php echo $type; ?>-grade-<?php echo $level['id']; ?>">
                                                    <?php echo $level['name']; ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="tab-content">
                                                <?php foreach ($gradeLevels as $level): ?>
                                                <div class="tab-pane fade <?php echo $level['id'] == 1 ? 'show active' : ''; ?>"
                                                     id="<?php echo $type; ?>-grade-<?php echo $level['id']; ?>">
                                                    <?php echo generateTableHTML($conn, $type, $selectedQuarter, $schools, $schoolYears, $level['id']); ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "footer.php"; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Handle quarter changes
        $('#quarter').on('change', function() {
            window.location.href = 'qualityComparative.php?quarter=' + $(this).val();
        });

        // Update active tab and grade level for exports
        $('.nav-pills a').on('click', function() {
            var tabId = $(this).attr('href').split('-')[0].substring(1);
            var gradeId = $(this).attr('href').split('-')[2] || '1';
            $('#exportCsvTab, #exportPdfTab').val(tabId);
            $('#exportCsvGrade, #exportPdfGrade').val(gradeId);
        });
    });
    </script>
</body>
</html>
