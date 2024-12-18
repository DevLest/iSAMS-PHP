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
        if ($type === 'sbfp') {
            $headers .= "<th>SY {$sy['start_year']}-{$sy['end_year']} (M)</th>";
            $headers .= "<th>SY {$sy['start_year']}-{$sy['end_year']} (F)</th>";
        } else {
            $headers .= "<th>SY {$sy['start_year']}-{$sy['end_year']}</th>";
        }
    }
    return $headers;
}

// Generate table content
function generateTableHTML($conn, $type, $quarter, $schools, $schoolYears) {
    $html = '<table class="table table-bordered">
             <thead><tr>' . generateSchoolYearHeaders($type, $schoolYears) . '</tr></thead>
             <tbody>';
    
    foreach ($schools as $school) {
        $html .= "<tr><td>{$school['name']}</td>";
        
        foreach ($schoolYears as $sy) {
            if ($type === 'cfs') {
                $query = "SELECT points, count FROM equity_assessment ea
                         JOIN school_year sy ON
                            (ea.year = sy.start_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month)
                            OR (ea.year = sy.end_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) < sy.start_month)
                         WHERE ea.school_id = ? 
                         AND ea.type = 'cfs'
                         AND ea.quarter = ?
                         AND sy.start_year = ?
                         ORDER BY points ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['start_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Add debug output
                if ($result->num_rows === 0) {
                    // $html .= "<td>No data found (ID: {$school['id']}, Q: $quarter, Y: {$sy['start_year']})</td>";
                } else {
                    $points = [];
                    while ($row = $result->fetch_assoc()) {
                        if ($row['count'] > 0) {
                            $points[] = "{$row['count']}";
                        }
                    }
                    $html .= "<td>" . implode(", ", $points) . "</td>";
                }
            }
            else if ($type === 'sbfp') {
                $query = "SELECT gender, SUM(count) as total 
                         FROM equity_assessment ea
                         JOIN school_year sy ON
                            (ea.year = sy.start_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month)
                            OR (ea.year = sy.end_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) < sy.start_month)
                         WHERE ea.school_id = ? 
                         AND ea.type = 'sbfp'
                         AND ea.quarter = ?
                         AND sy.start_year = ?
                         GROUP BY gender";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['start_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $male = $female = 0;
                while ($row = $result->fetch_assoc()) {
                    if ($row['gender'] == 1) $male = $row['total'];
                    if ($row['gender'] == 2) $female = $row['total'];
                }
                $html .= "<td>$male</td><td>$female</td>";
            }
            else { // wash
                $query = "SELECT count FROM equity_assessment ea
                         JOIN school_year sy ON
                            (ea.year = sy.start_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month)
                            OR (ea.year = sy.end_year AND MONTH(CONCAT(ea.year, '-', LPAD(ea.quarter * 3 - 2, 2, '0'), '-01')) < sy.start_month)
                         WHERE ea.school_id = ? 
                         AND ea.type = 'wash'
                         AND ea.quarter = ?
                         AND sy.start_year = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['start_year']); 
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stars = $row ? intval($row['count']) : 0;
                
                // Generate stars with colors and proper alignment
                $html .= '<td class="text-center">';  // Add text-center class
                $html .= '<div class="d-inline-block">';  // Add inline-block container
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $stars) {
                        $html .= '<i class="fas fa-star text-warning"></i>'; // Filled star
                    } else {
                        $html .= '<i class="far fa-star"></i>'; // Empty star
                    }
                }
                $html .= " ($stars)</div></td>";
            }
        }
        $html .= "</tr>";
    }
    
    $html .= '</tbody></table>';
    return $html;
}

// Export functions
function exportCSV($conn, $type, $quarter, $schools, $schoolYears) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="equity_summary.csv"');
    $output = fopen('php://output', 'w');
    
    // CSV headers
    $headers = ['School Name'];
    foreach ($schoolYears as $sy) {
        if ($type === 'sbfp') {
            $headers[] = "SY {$sy['start_year']}-{$sy['end_year']} (M)";
            $headers[] = "SY {$sy['start_year']}-{$sy['end_year']} (F)";
        } else {
            $headers[] = "SY {$sy['start_year']}-{$sy['end_year']}";
        }
    }
    fputcsv($output, $headers);
    
    // Data rows
    foreach ($schools as $school) {
        $row = [$school['name']];
        
        foreach ($schoolYears as $sy) {
            if ($type === 'cfs') {
                $query = "SELECT points, count FROM equity_assessment 
                         WHERE school_id = ? AND type = 'cfs' 
                         AND quarter = ? AND year = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $points = '';
                while ($rowData = $result->fetch_assoc()) {
                    $points .= "{$rowData['count']}({$rowData['points']}) ";
                }
                $row[] = $points;
            }
            else if ($type === 'sbfp') {
                $query = "SELECT gender, SUM(count) as total 
                         FROM equity_assessment 
                         WHERE school_id = ? AND type = 'sbfp' 
                         AND quarter = ? AND year = ?
                         GROUP BY gender";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $male = $female = 0;
                while ($rowData = $result->fetch_assoc()) {
                    if ($rowData['gender'] == 1) $male = $rowData['total'];
                    if ($rowData['gender'] == 2) $female = $rowData['total'];
                }
                $row[] = $male;
                $row[] = $female;
            }
            else {
                $query = "SELECT count FROM equity_assessment 
                         WHERE school_id = ? AND type = 'wash' 
                         AND quarter = ? AND year = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $row[] = $result->fetch_assoc()['count'] ?? 0;
            }
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function exportPDF($conn, $type, $quarter, $schools, $schoolYears) {
    // Get user information
    $userId = $_SESSION['user_id'];
    $userQuery = "SELECT u.*, s.name as school_name, s.address as school_address 
                  FROM users u 
                  LEFT JOIN schools s ON u.school_id = s.id 
                  WHERE u.id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $userInfo = $stmt->get_result()->fetch_assoc();

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
            .user-info { text-align: right; margin-bottom: 20px; font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f5f5f5; }
        </style>
    </head>
    <body>
        <div class="user-info">
            <p>Generated by: ' . htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']) . '</p>';

    // Add school info if available
    if (!empty($userInfo['school_name'])) {
        $html .= '<p>School: ' . htmlspecialchars($userInfo['school_name']) . '</p>';
        if (!empty($userInfo['school_address'])) {
            $html .= '<p>Address: ' . htmlspecialchars($userInfo['school_address']) . '</p>';
        }
    }

    $html .= '</div>
        <div class="header">
            <h1>SMEA - School Management Enrollment Analytics</h1>
            <p>Equity Assessment Report - ' . ucfirst($type) . '</p>
            <p>Quarter: ' . $quarter . '</p>
            <p>Generated on: ' . date('F d, Y') . '</p>
        </div>';

    // Add table
    $html .= '<table class="table table-bordered">
              <thead><tr>' . generateSchoolYearHeaders($type, $schoolYears) . '</tr></thead>
              <tbody>';
    
    foreach ($schools as $school) {
        $html .= "<tr><td>{$school['name']}</td>";
        
        foreach ($schoolYears as $sy) {
            if ($type === 'cfs') {
                $query = "SELECT points, count FROM equity_assessment 
                         WHERE school_id = ? AND type = 'cfs' 
                         AND quarter = ? AND year = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $points = '';
                while ($row = $result->fetch_assoc()) {
                    $points .= "{$row['count']}({$row['points']}) ";
                }
                $html .= "<td>$points</td>";
            }
            else if ($type === 'sbfp') {
                $query = "SELECT gender, SUM(count) as total 
                         FROM equity_assessment 
                         WHERE school_id = ? AND type = 'sbfp' 
                         AND quarter = ? AND year = ?
                         GROUP BY gender";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $male = $female = 0;
                while ($row = $result->fetch_assoc()) {
                    if ($row['gender'] == 1) $male = $row['total'];
                    if ($row['gender'] == 2) $female = $row['total'];
                }
                $html .= "<td>$male</td><td>$female</td>";
            }
            else { // wash
                $query = "SELECT count FROM equity_assessment 
                         WHERE school_id = ? AND type = 'wash' 
                         AND quarter = ? AND year = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $school['id'], $quarter, $sy['end_year']);
                $stmt->execute();
                $result = $stmt->get_result();
                $stars = $result->fetch_assoc()['count'] ?? 0;
                $html .= "<td>$stars</td>";
            }
        }
        $html .= "</tr>";
    }
    
    $html .= '</tbody></table></body></html>';

    // Output the HTML and trigger print
    echo $html;
    echo "<script>window.print();</script>";
    exit;
}

// Handle export requests
if (isset($_POST['export_csv'])) {
    $activeTab = $_POST['activeTab'] ?? 'cfs';
    exportCSV($conn, $activeTab, $selectedQuarter, $schools, $schoolYears);
}

if (isset($_POST['export_pdf'])) {
    $activeTab = $_POST['activeTab'] ?? 'cfs';
    exportPDF($conn, $activeTab, $selectedQuarter, $schools, $schoolYears);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equity Assessment Summary - SMEA</title>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once "navbar.php"; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Equity Assessment Summary</h1>
                    <p class="mb-4">View and export equity assessment data</p>

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
                            <!-- <form action="" method="post" style="display: inline-block;">
                                <input type="hidden" name="activeTab" id="exportCsvTab" value="cfs">
                                <button type="submit" class="btn btn-info" name="export_csv">
                                    <i class="fas fa-file-csv mr-2"></i>Export CSV
                                </button>
                            </form> -->
                            <form action="" method="post" style="display: inline-block;">
                                <input type="hidden" name="activeTab" id="exportPdfTab" value="cfs">
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
                                    <a class="nav-link active" data-toggle="pill" href="#cfs">Child-Friendly School</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#sbfp">School-based Feeding Program</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#wash">Water Sanitation</a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="cfs">
                                    <?php echo generateTableHTML($conn, 'cfs', $selectedQuarter, $schools, $schoolYears); ?>
                                </div>
                                <div class="tab-pane fade" id="sbfp">
                                    <?php echo generateTableHTML($conn, 'sbfp', $selectedQuarter, $schools, $schoolYears); ?>
                                </div>
                                <div class="tab-pane fade" id="wash">
                                    <?php echo generateTableHTML($conn, 'wash', $selectedQuarter, $schools, $schoolYears); ?>
                                </div>
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
            window.location.href = 'equityComparative.php?quarter=' + $(this).val();
        });

        // Update active tab for exports
        $('.nav-pills a').on('click', function() {
            var tabId = $(this).attr('href').substring(1);
            $('#exportCsvTab, #exportPdfTab').val(tabId);
        });
    });
    </script>
</body>
</html>