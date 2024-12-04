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
$schoolYearQuery = "SELECT * FROM school_year WHERE start_year >= $currentYear AND start_year <= $nextYear ORDER BY start_year ASC";
$schoolYears = $conn->query($schoolYearQuery)->fetch_all(MYSQLI_ASSOC);

// Get schools
$schoolQuery = "SELECT * FROM schools WHERE id <= 17 ORDER BY name";
$schools = $conn->query($schoolQuery)->fetch_all(MYSQLI_ASSOC);

// Get grade levels
$gradeLevelQuery = "SELECT * FROM grade_level ORDER BY id";
$gradeLevels = $conn->query($gradeLevelQuery)->fetch_all(MYSQLI_ASSOC);

// Generate table headers for school years
function generateSchoolYearHeaders($type, $schoolYears) {
    $headers = '<th>School Name</th>';
    foreach ($schoolYears as $sy) {
        $headers .= "<th>SY {$sy['start_year']}-{$sy['end_year']} (M)</th>";
        $headers .= "<th>SY {$sy['start_year']}-{$sy['end_year']} (F)</th>";
    }
    return $headers;
}

// Generate table content
function generateTableHTML($conn, $type, $quarter, $schools, $schoolYears, $gradeLevel) {
    $html = '<table class="table table-bordered">
             <thead><tr>' . generateSchoolYearHeaders($type, $schoolYears) . '</tr></thead>
             <tbody>';
    
    foreach ($schools as $school) {
        $html .= "<tr><td>{$school['name']}</td>";
        
        foreach ($schoolYears as $sy) {
            $query = "SELECT gender, SUM(count) as total 
                     FROM rwb_assessment 
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
            
            $maleClass = $male == 0 ? ' class="text-muted"' : '';
            $femaleClass = $female == 0 ? ' class="text-muted"' : '';
            
            $html .= "<td{$maleClass}>{$male}</td><td{$femaleClass}>{$female}</td>";
        }
        $html .= "</tr>";
    }
    
    $html .= '</tbody></table>';
    return $html;
}

// Export functions
function exportCSV($conn, $type, $quarter, $schools, $schoolYears, $gradeLevel) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="rwb_summary.csv"');
    $output = fopen('php://output', 'w');
    
    // CSV headers
    $headers = ['School Name'];
    foreach ($schoolYears as $sy) {
        $headers[] = "SY {$sy['start_year']}-{$sy['end_year']} (M)";
        $headers[] = "SY {$sy['start_year']}-{$sy['end_year']} (F)";
    }
    fputcsv($output, $headers);
    
    // Data rows
    foreach ($schools as $school) {
        $row = [$school['name']];
        
        foreach ($schoolYears as $sy) {
            $query = "SELECT gender, SUM(count) as total 
                     FROM rwb_assessment 
                     WHERE school_id = ? AND type = ? 
                     AND quarter = ? AND year = ? AND grade_level = ?
                     GROUP BY gender";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isiii', $school['id'], $type, $quarter, $sy['end_year'], $gradeLevel);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $male = $female = 0;
            while ($row_data = $result->fetch_assoc()) {
                if ($row_data['gender'] == 1) $male = $row_data['total'];
                if ($row_data['gender'] == 2) $female = $row_data['total'];
            }
            $row[] = $male;
            $row[] = $female;
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function exportPDF($conn, $type, $quarter, $schools, $schoolYears, $gradeLevel) {
    // Start HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { margin: 0; color: #4e73df; font-size: 24px; }
            .header p { margin: 5px 0; color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f5f5f5; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>SMEA - School Management Enrollment Analytics</h1>
            <p>RWB Assessment Report - ' . ucfirst($type) . '</p>
            <p>Quarter: ' . $quarter . '</p>
            <p>Generated on: ' . date('F j, Y ') . '</p>
        </div>';

    $html .= generateTableHTML($conn, $type, $quarter, $schools, $schoolYears, $gradeLevel);
    $html .= '</body></html>';

    echo $html;
    echo "<script>window.print();</script>";
    exit;
}

// Handle export requests
if (isset($_POST['export_csv'])) {
    $activeTab = $_POST['activeTab'] ?? 'displaced';
    $activeGrade = $_POST['activeGrade'] ?? 1;
    exportCSV($conn, $activeTab, $selectedQuarter, $schools, $schoolYears, $activeGrade);
}

if (isset($_POST['export_pdf'])) {
    $activeTab = $_POST['activeTab'] ?? 'displaced';
    $activeGrade = $_POST['activeGrade'] ?? 1;
    exportPDF($conn, $activeTab, $selectedQuarter, $schools, $schoolYears, $activeGrade);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RWB Assessment Summary - SMEA</title>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once "navbar.php"; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">RWB Assessment Summary</h1>
                    <p class="mb-4">View and export RWB assessment data</p>

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
                                <input type="hidden" name="activeTab" id="exportCsvTab" value="displaced">
                                <input type="hidden" name="activeGrade" id="exportCsvGrade" value="1">
                                <button type="submit" class="btn btn-info" name="export_csv">
                                    <i class="fas fa-file-csv mr-2"></i>Export CSV
                                </button>
                            </form>
                            <form action="" method="post" style="display: inline-block;">
                                <input type="hidden" name="activeTab" id="exportPdfTab" value="displaced">
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
                                    <a class="nav-link active" data-toggle="pill" href="#displaced">Displaced Learner</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#bullying">Bullying and Child Abuse</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#equipped">Equipped Learners</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#bmi">Learners with 80% BMI</a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content">
                                <?php foreach(['displaced', 'bullying', 'equipped', 'bmi'] as $type): ?>
                                <div class="tab-pane fade <?php echo $type === 'displaced' ? 'show active' : ''; ?>" id="<?php echo $type; ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills">
                                                <?php foreach ($gradeLevels as $level): ?>
                                                <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>" 
                                                   data-toggle="pill" 
                                                   href="#<?php echo $type; ?>-grade-<?php echo $level['id']; ?>" 
                                                   role="tab">
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
            window.location.href = 'rwbComparative.php?quarter=' + $(this).val();
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