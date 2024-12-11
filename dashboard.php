<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

// Get current school year
$schoolYearQuery = "SELECT * FROM school_year 
                    WHERE (YEAR(NOW()) BETWEEN start_year AND end_year) 
                    OR (YEAR(NOW()) = end_year AND MONTH(NOW()) <= end_month)
                    OR (YEAR(NOW()) = start_year AND MONTH(NOW()) >= start_month)
                    LIMIT 1";
$schoolYearResult = $conn->query($schoolYearQuery);
$schoolYear = $schoolYearResult->fetch_assoc();

// Calculate current quarter based on school year
$startMonth = $schoolYear['start_month'];
$currentMonth = date('n');
$adjustedMonth = ($currentMonth < $startMonth) ? $currentMonth + 12 : $currentMonth;
$currentQuarter = ceil(($adjustedMonth - $startMonth + 1) / 3);
$year = $schoolYear['end_year'];

$reportType = 'enrollment'; // Default value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_type'])) {
    $reportType = $_POST['report_type'];
}

$sql = "SELECT 
    SUM(CASE 
        WHEN quarter = 1 AND type = '$reportType' 
        AND (
            (year IN ($year - 1, $year) AND MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth)
        )
        THEN count ELSE 0 END
    ) AS q1_total,
    SUM(CASE 
        WHEN quarter = 2 AND type = '$reportType'
        AND (
            (year IN ($year - 1, $year) AND MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth)
        )
        THEN count ELSE 0 END
    ) AS q2_total,
    SUM(CASE 
        WHEN quarter = 3 AND type = '$reportType'
        AND (
            (year IN ($year - 1, $year) AND MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth)
        )
        THEN count ELSE 0 END
    ) AS q3_total,
    SUM(CASE 
        WHEN quarter = 4 AND type = '$reportType'
        AND (
            (year IN ($year - 1, $year) AND MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth)
        )
        THEN count ELSE 0 END
    ) AS q4_total,
    MAX(sy.start_year) as start_year,
    MAX(sy.end_year) as end_year,
    MAX(sy.start_month) as start_month,
    MAX(sy.end_month) as end_month
FROM attendance_summary a
JOIN school_year sy ON 
    (a.year BETWEEN sy.start_year AND sy.end_year)
    OR (a.year = sy.end_year AND MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) <= sy.end_month)
    OR (a.year = sy.start_year AND MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month)
WHERE a.year IN ($year - 1, $year)
GROUP BY sy.id";
$sum = $conn->query($sql);
$sum = $sum->fetch_assoc();

$schoolsQuery = "SELECT 
    s.id,
    s.name,
    COALESCE(SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) THEN a.count 
        ELSE 0 
    END), 0) as total_count,
    (SELECT COALESCE(SUM(count), 0) 
     FROM attendance_summary 
     WHERE type = '$reportType' 
     AND (
         (MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND year = $year - 1)
         OR (MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND year = $year)
     )
    ) as overall_total
FROM schools s
LEFT JOIN attendance_summary a ON s.id = a.school_id 
    AND a.type = '$reportType' 
    AND (
        (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
        OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
    )
GROUP BY s.id, s.name
ORDER BY s.id";

$schools = $conn->query($schoolsQuery);

$schoolStats = "";
if ($schools->num_rows > 0) {
    while($school = $schools->fetch_assoc()) {
        $percentage = $school['overall_total'] > 0 
            ? round(($school['total_count'] / $school['overall_total']) * 100, 1)
            : 0;
            
        $schoolStats .= "
            <h4 class='small font-weight-bold'>{$school['name']} <span
                    class='float-right'>{$school['total_count']} ({$percentage}%)</span></h4>
            <div class='progress mb-4'>
                <div class='progress-bar bg-info' role='progressbar' style='width: {$percentage}%'
                    aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
            </div>";
    }
}

// Get school year data for the chart
$chartSql = "SELECT 
    CONCAT(sy.start_year, '-', sy.end_year) as school_year,
    SUM(CASE 
        WHEN (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month AND a.year = sy.start_year)
        OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < sy.start_month AND a.year = sy.end_year)
        THEN a.count 
        ELSE 0 
    END) as total
FROM attendance_summary a
JOIN school_year sy ON 
    (a.year = sy.start_year AND MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= sy.start_month)
    OR (a.year = sy.end_year AND MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < sy.start_month)
WHERE a.type = '$reportType'
GROUP BY sy.id, sy.start_year, sy.end_year
ORDER BY sy.start_year ASC";

    $chartResult = $conn->query($chartSql);
    $yearLabels = [];
    $yearValues = [];

    while($row = $chartResult->fetch_assoc()) {
        $yearLabels[] = $row['school_year'];
        $yearValues[] = $row['total'];
    }

    // Pass the data to JavaScript
    echo "<script>
        var yearLabels = " . json_encode($yearLabels) . ";
        var yearValues = " . json_encode($yearValues) . ";
    </script>";

// Add this query to get the pie chart data
$pieChartQuery = "SELECT 
    SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) AND a.type = 'als' 
        THEN a.count 
        ELSE 0 
    END) as als_count,
    SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) AND g.type = 'elem' AND a.type = '$reportType'
        THEN a.count 
        ELSE 0 
    END) as elem_count,
    SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) AND g.type IN ('jhs', 'shs') AND a.type = '$reportType'
        THEN a.count 
        ELSE 0 
    END) as secondary_count
FROM attendance_summary a
LEFT JOIN grade_level g ON a.grade_level_id = g.id
WHERE a.year IN ($year - 1, $year)";

$pieResult = $conn->query($pieChartQuery);
$pieData = $pieResult->fetch_assoc();

// Pass data to JavaScript
echo "<script>
    var pieChartData = {
        als: " . ($pieData['als_count'] ?? 0) . ",
        elementary: " . ($pieData['elem_count'] ?? 0) . ",
        secondary: " . ($pieData['secondary_count'] ?? 0) . "
    };
</script>";

// Debug issues query
$issuesQuery = "SELECT ic.*, s.name as school_name 
                FROM issues_and_concerns ic 
                LEFT JOIN schools s ON ic.school_id = s.id
                WHERE ic.type = 'attendance'"; // Removed all other conditions temporarily
                
$issuesResult = $conn->query($issuesQuery);
if (!$issuesResult) {
    echo "Issues Query Error: " . $conn->error;
} else {
    echo "<!-- Found " . $issuesResult->num_rows . " issues records -->"; // Debug comment
}

// Debug trend query
$trendQuery = "SELECT 
    s.id as school_id,
    s.name as school_name,
    SUM(CASE WHEN a.year = $year THEN a.count ELSE 0 END) as this_year,
    SUM(CASE WHEN a.year = $year - 1 THEN a.count ELSE 0 END) as last_year,
    a.type,
    $currentQuarter as quarter
FROM attendance_summary a
JOIN schools s ON a.school_id = s.id
WHERE a.type = '$reportType'
GROUP BY s.id, s.name, a.type
ORDER BY this_year DESC";

$trendResult = $conn->query($trendQuery);
if (!$trendResult) {
    echo "Trend Query Error: " . $conn->error;
} else {
    echo "<!-- Found " . $trendResult->num_rows . " trend records -->"; // Debug comment
}

// Add this query before the closing PHP tag and before the body tag
$comparisonQuery = "SELECT 
    s.id,
    s.name,
    COALESCE(SUM(CASE 
        WHEN a.type = 'enrollment' AND (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) THEN a.count 
        ELSE 0 
    END), 0) as enrollment_count,
    COALESCE(SUM(CASE 
        WHEN a.type = 'dropouts' AND (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) THEN a.count 
        ELSE 0 
    END), 0) as dropout_count
FROM schools s
LEFT JOIN attendance_summary a ON s.id = a.school_id 
    AND a.type IN ('enrollment', 'dropouts') 
    AND (
        (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
        OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
    )
GROUP BY s.id, s.name
ORDER BY s.name";

$comparisonResult = $conn->query($comparisonQuery);

// Add this query to get gender distribution data
$genderQuery = "SELECT 
    SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) AND a.gender = 'male' AND a.type = '$reportType'
        THEN a.count 
        ELSE 0 
    END) as male_count,
    SUM(CASE 
        WHEN (
            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
        ) AND a.gender = 'female' AND a.type = '$reportType'
        THEN a.count 
        ELSE 0 
    END) as female_count
FROM attendance_summary a
WHERE a.year IN ($year - 1, $year)";

$genderResult = $conn->query($genderQuery);
$genderData = $genderResult->fetch_assoc();

// Pass gender data to JavaScript
echo "<script>
    var genderData = {
        male: " . ($genderData['male_count'] ?? 0) . ",
        female: " . ($genderData['female_count'] ?? 0) . "
    };
</script>";
?>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include_once "sidebar.php"; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <?php include_once "navbar.php"?>
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                        <div class="dropdown">
                            <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm dropdown-toggle" 
                                    type="button" 
                                    id="reportDropdown" 
                                    data-toggle="dropdown" 
                                    aria-haspopup="true" 
                                    aria-expanded="false">
                                <i class="fas fa-filter fa-sm text-white-50"></i> Filter Report
                            </button>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="reportDropdown">
                                <form action="dashboard.php" method="POST">
                                    <button type="submit" name="report_type" value="enrollment" class="dropdown-item">
                                        Enrollment
                                    </button>
                                    <button type="submit" name="report_type" value="dropouts" class="dropdown-item">
                                        Drop Out
                                    </button>
                                    <button type="submit" name="report_type" value="graduates" class="dropdown-item">
                                        Graduates
                                    </button>
                                    <button type="submit" name="report_type" value="repeaters" class="dropdown-item">
                                        Repeaters
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                1st Quarter</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($sum['q1_total'], 0, '.', ',');?></div>
                                        </div>
                                        <div class="col-auto">
                                            <!-- <i class="fas fa-calendar fa-2x text-gray-300"></i> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                2nd Quarter</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($sum['q2_total'], 0, '.', ',');?></div>
                                        </div>
                                        <div class="col-auto">
                                            <!-- <i class="fas fa-dollar-sign fa-2x text-gray-300"></i> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                3rd Quarter</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($sum['q3_total'], 0, '.', ',');?></div>
                                        </div>
                                        <div class="col-auto">
                                            <!-- <i class="fas fa-dollar-sign fa-2x text-gray-300"></i> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                4th Quarter</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($sum['q4_total'], 0, '.', ',');?></div>
                                        </div>
                                        <div class="col-auto">
                                            <!-- <i class="fas fa-dollar-sign fa-2x text-gray-300"></i> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary"><?php echo ucwords($reportType);?> Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-bar">
                                            <canvas id="enrollmentBarChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Pie Graph Percentage</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Dropdown Header:</div>
                                            <a class="dropdown-item" href="#">Action</a>
                                            <a class="dropdown-item" href="#">Another action</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Something else here</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> ALS
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> ELEMENTARY
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-info"></i> JHS/SHS
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Attendance Analysis Column -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Analysis & Interventions</h6>
                                    <span class="badge badge-info"><?php echo $trendResult->num_rows; ?> Schools Report</span>
                                </div>
                                <div class="card-body">
                                    <?php if($trendResult->num_rows > 0): ?>
                                        <div id="schoolCards" class="carousel slide" data-ride="carousel" data-interval="false">
                                            <div class="carousel-inner">
                                                <?php 
                                                $counter = 0;
                                                $itemsPerPage = 3;
                                                $trends = [];
                                                
                                                while($trend = $trendResult->fetch_assoc()) {
                                                    $trends[] = $trend;
                                                }
                                                
                                                for($i = 0; $i < count($trends); $i += $itemsPerPage):
                                                    $active = $i === 0 ? 'active' : '';
                                                ?>
                                                    <div class="carousel-item <?php echo $active; ?>">
                                                        <?php 
                                                        for($j = $i; $j < min($i + $itemsPerPage, count($trends)); $j++):
                                                            $trend = $trends[$j];
                                                            $change = $trend['this_year'] - $trend['last_year'];
                                                            $percentChange = $trend['last_year'] > 0 ? 
                                                                round(($change / $trend['last_year']) * 100, 1) : 
                                                                ($trend['this_year'] > 0 ? 100 : 0);
                                                            
                                                            $isDecrease = $change < 0;
                                                            $cardClass = $isDecrease ? 'border-left-warning' : 'border-left-success';
                                                            $textClass = $isDecrease ? 'text-danger' : 'text-success';
                                                            $changeText = $isDecrease ? 'decreased' : 'increased';
                                                        ?>
                                                            <div class="school-card mb-3 <?php echo $cardClass; ?>">
                                                                <h6 class="font-weight-bold mb-1"><?php echo $trend['school_name']; ?></h6>
                                                                <p class="<?php echo $textClass; ?> mb-2">
                                                                    <?php echo ucfirst($reportType); ?> <?php echo $changeText; ?> by <?php echo abs($percentChange); ?>% compared to last year
                                                                    <small class="d-block text-muted">
                                                                        (<?php echo $trend['this_year']; ?> vs <?php echo $trend['last_year']; ?> students)
                                                                    </small>
                                                                </p>
                                                                <?php if($isDecrease): ?>
                                                                <div class="intervention-list">
                                                                    <div class="intervention-item">
                                                                        <i class="fas fa-calendar-check text-primary"></i>
                                                                        <span>Schedule parent-teacher conference</span>
                                                                    </div>
                                                                    <div class="intervention-item">
                                                                        <i class="fas fa-home text-info"></i>
                                                                        <span>Conduct home visitation</span>
                                                                    </div>
                                                                    <div class="intervention-item">
                                                                        <i class="fas fa-list-ul text-warning"></i>
                                                                        <span>Review reported issues</span>
                                                                    </div>
                                                                </div>
                                                                <?php else: ?>
                                                                <div class="intervention-list">
                                                                    <div class="intervention-item">
                                                                        <i class="fas fa-chart-line text-success"></i>
                                                                        <span>Maintain positive growth strategies</span>
                                                                    </div>
                                                                    <div class="intervention-item">
                                                                        <i class="fas fa-star text-warning"></i>
                                                                        <span>Document best practices</span>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if(count($trends) > $itemsPerPage): ?>
                                                <div class="carousel-controls">
                                                    <a class="carousel-control-prev" href="#schoolCards" role="button" data-slide="prev">
                                                        <span class="carousel-control-prev-icon bg-secondary rounded-circle" aria-hidden="true"></span>
                                                        <span class="sr-only">Previous</span>
                                                    </a>
                                                    <a class="carousel-control-next" href="#schoolCards" role="button" data-slide="next">
                                                        <span class="carousel-control-next-icon bg-secondary rounded-circle" aria-hidden="true"></span>
                                                        <span class="sr-only">Next</span>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Issues Column -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Issues & Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0" id="issuesTable">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>School</th>
                                                    <th>Issue</th>
                                                    <th width="100">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php while($issue = $issuesResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="small"><?php echo $issue['school_name']; ?></td>
                                                    <td class="small">
                                                        <?php echo $issue['issues']; ?>
                                                        <?php if(!empty($issue['actions_taken'])): ?>
                                                            <br>
                                                            <small class="text-success">
                                                                <i class="fas fa-check-circle"></i> 
                                                                <?php echo $issue['actions_taken']; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if(empty($issue['actions_taken'])): ?>
                                                            <span class="badge badge-danger">Pending</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">Addressed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Enrollment Distribution Column -->
                        <div class="col-lg-6 mb-4">
                            <!-- Project Card Example -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Schools Enrollment Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Specific query for enrollment distribution
                                    $enrollmentQuery = "SELECT 
                                        s.id,
                                        s.name,
                                        COALESCE(SUM(CASE 
                                            WHEN (
                                                (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
                                                OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
                                            ) THEN a.count 
                                            ELSE 0 
                                        END), 0) as total_count,
                                        (SELECT COALESCE(SUM(count), 0) 
                                         FROM attendance_summary 
                                         WHERE type = 'enrollment'
                                         AND (
                                             (MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND year = $year - 1)
                                             OR (MONTH(CONCAT(year, '-', LPAD(quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND year = $year)
                                         )
                                        ) as overall_total
                                    FROM schools s
                                    LEFT JOIN attendance_summary a ON s.id = a.school_id 
                                        AND a.type = 'enrollment'
                                        AND (
                                            (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) >= $startMonth AND a.year = $year - 1)
                                            OR (MONTH(CONCAT(a.year, '-', LPAD(a.quarter * 3 - 2, 2, '0'), '-01')) < $startMonth AND a.year = $year)
                                        )
                                    GROUP BY s.id, s.name
                                    ORDER BY s.id";

                                    $enrollments = $conn->query($enrollmentQuery);
                                    $enrollmentStats = "";

                                    if ($enrollments && $enrollments->num_rows > 0) {
                                        while($enrollment = $enrollments->fetch_assoc()) {
                                            $percentage = $enrollment['overall_total'] > 0 
                                                ? round(($enrollment['total_count'] / $enrollment['overall_total']) * 100, 1)
                                                : 0;
                                                
                                            $enrollmentStats .= "
                                                <h4 class='small font-weight-bold'>{$enrollment['name']} <span
                                                        class='float-right'>{$enrollment['total_count']} ({$percentage}%)</span></h4>
                                                <div class='progress mb-4'>
                                                    <div class='progress-bar bg-info' role='progressbar' style='width: {$percentage}%'
                                                        aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
                                                </div>";
                                        }
                                        echo $enrollmentStats;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Dropout Distribution Column -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Schools Dropout Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $dropoutStats = "";
                                    
                                    $dropoutQuery = "SELECT 
                                        s.id,
                                        s.name,
                                        COALESCE(SUM(CASE 
                                            WHEN a.type = 'dropouts' THEN a.count 
                                            ELSE 0 
                                        END), 0) as total_count,
                                        (SELECT COALESCE(SUM(count), 0) 
                                         FROM attendance_summary 
                                         WHERE type = 'dropouts'
                                        ) as overall_total
                                    FROM schools s
                                    LEFT JOIN attendance_summary a ON s.id = a.school_id 
                                    GROUP BY s.id, s.name
                                    ORDER BY s.id";
                                    
                                    // Debug the query
                                    
                                    $dropouts = $conn->query($dropoutQuery);
                                    
                                    
                                    if ($dropouts && $dropouts->num_rows > 0) {
                                        while($dropout = $dropouts->fetch_assoc()) {
                                            
                                            $percentage = $dropout['overall_total'] > 0 
                                                ? round(($dropout['total_count'] / $dropout['overall_total']) * 100, 1)
                                                : 0;
                                                
                                            $dropoutStats .= "
                                                <h4 class='small font-weight-bold'>{$dropout['name']} <span
                                                        class='float-right'>{$dropout['total_count']} ({$percentage}%)</span></h4>
                                                <div class='progress mb-4'>
                                                    <div class='progress-bar bg-danger' role='progressbar' style='width: {$percentage}%'
                                                        aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
                                                </div>";
                                        }
                                        echo $dropoutStats;
                                    } else {
                                        echo "<p class='text-muted'>No dropout data available.</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment vs Dropouts Comparison -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Enrollment vs Dropouts Comparison</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>School</th>
                                                    <th class="text-center">Enrollment</th>
                                                    <th class="text-center">Dropouts</th>
                                                    <th class="text-center">Retention Rate</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($comparison = $comparisonResult->fetch_assoc()): 
                                                    $retentionRate = $comparison['enrollment_count'] > 0 
                                                        ? round((($comparison['enrollment_count'] - $comparison['dropout_count']) / $comparison['enrollment_count']) * 100, 1)
                                                        : 0;
                                                    
                                                    // Determine status and badge color
                                                    if ($retentionRate >= 90) {
                                                        $status = "Excellent";
                                                        $badgeColor = "success";
                                                    } elseif ($retentionRate >= 80) {
                                                        $status = "Good";
                                                        $badgeColor = "info";
                                                    } elseif ($retentionRate >= 70) {
                                                        $status = "Fair";
                                                        $badgeColor = "warning";
                                                    } else {
                                                        $status = "Needs Attention";
                                                        $badgeColor = "danger";
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><?php echo $comparison['name']; ?></td>
                                                        <td class="text-center"><?php echo number_format($comparison['enrollment_count']); ?></td>
                                                        <td class="text-center">
                                                            <?php if($comparison['dropout_count'] > 0): ?>
                                                                <span class="text-danger">
                                                                    <?php echo number_format($comparison['dropout_count']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                0
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-<?php echo $badgeColor; ?>" 
                                                                     role="progressbar" 
                                                                     style="width: <?php echo $retentionRate; ?>%"
                                                                     aria-valuenow="<?php echo $retentionRate; ?>" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100">
                                                                    <?php echo $retentionRate; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-<?php echo $badgeColor; ?>">
                                                                <?php echo $status; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; YUMI 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php include_once "logout-modal.php"?>
    <?php include_once "footer.php"?>

</body>

</html>