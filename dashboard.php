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

$reportType = 'enrollment'; // Default value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_type'])) {
    $reportType = $_POST['report_type'];
}

$sql = "SELECT 
        SUM(CASE WHEN quarter = 1 AND type = '$reportType' THEN count ELSE 0 END) AS q1_total,
        SUM(CASE WHEN quarter = 2 AND type = '$reportType' THEN count ELSE 0 END) AS q2_total,
        SUM(CASE WHEN quarter = 3 AND type = '$reportType' THEN count ELSE 0 END) AS q3_total,
        SUM(CASE WHEN quarter = 4 AND type = '$reportType' THEN count ELSE 0 END) AS q4_total
        FROM attendance_summary 
        WHERE year = $year;";
$sum = $conn->query($sql);
$sum = $sum->fetch_assoc();

$schoolsQuery = "SELECT 
    s.id,
    s.name,
    COALESCE(SUM(a.count), 0) as total_count,
    (SELECT COALESCE(SUM(count), 0) 
     FROM attendance_summary 
     WHERE type = '$reportType' 
     AND year = $year) as overall_total
FROM schools s
LEFT JOIN attendance_summary a ON s.id = a.school_id 
    AND a.type = '$reportType' 
    AND a.year = $year
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
        SUM(a.count) as total
    FROM attendance_summary a
    JOIN school_year sy ON a.year = sy.end_year
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
        WHEN a.type = 'als' THEN a.count 
        ELSE 0 
    END) as als_count,
    SUM(CASE 
        WHEN g.type = 'elem' THEN a.count 
        ELSE 0 
    END) as elem_count,
    SUM(CASE 
        WHEN g.type IN ('jhs', 'shs') THEN a.count 
        ELSE 0 
    END) as secondary_count
FROM attendance_summary a
LEFT JOIN grade_level g ON a.grade_level_id = g.id
WHERE a.type = '$reportType' 
AND a.year = $year";

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

// Add after the existing SQL queries
$issuesQuery = "SELECT ic.*, s.name as school_name 
                FROM issues_and_concerns ic 
                LEFT JOIN schools s ON ic.school_id = s.id
                WHERE ic.type = 'attendance' 
                AND ic.year = $year 
                AND ic.quarter = $currentQuarter
                ORDER BY ic.updated_at DESC";
$issuesResult = $conn->query($issuesQuery);

// Get trend analysis
$trendQuery = "SELECT 
    s.name as school_name,
    SUM(CASE WHEN a.quarter = $currentQuarter-1 THEN a.count ELSE 0 END) as last_quarter,
    SUM(CASE WHEN a.quarter = $currentQuarter THEN a.count ELSE 0 END) as this_quarter,
    a.type
FROM attendance_summary a
JOIN schools s ON a.school_id = s.id
WHERE a.year = $year 
AND a.type IN ('leavers', 'enrollment')
GROUP BY s.id, a.type
HAVING this_quarter < last_quarter
ORDER BY (last_quarter - this_quarter) DESC";
$trendResult = $conn->query($trendQuery);
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
                                    <button type="submit" name="report_type" value="dropout" class="dropdown-item">
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
                                    <span class="badge badge-warning"><?php echo $trendResult->num_rows; ?> Schools Need Attention</span>
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
                                                            $decrease = $trend['last_quarter'] - $trend['this_quarter'];
                                                            $percentChange = round(($decrease / $trend['last_quarter']) * 100, 1);
                                                        ?>
                                                            <div class="school-card mb-3 border-left-warning">
                                                                <h6 class="font-weight-bold mb-1"><?php echo $trend['school_name']; ?></h6>
                                                                <p class="text-danger mb-2">Enrollment decreased by <?php echo $percentChange; ?>%</p>
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

                        <!-- Content Column -->
                        <div class="col-lg-12 mb-4">

                            <!-- Project Card Example -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Schools <?php echo ucwords($reportType); ?> Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <?php echo $schoolStats; ?>
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