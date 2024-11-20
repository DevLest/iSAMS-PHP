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
$current_user_id = $_SESSION['user_id'];

if (isset($_POST['quarter'])) {
    $selectedQuarter = $_POST['quarter'];
} else {
    $selectedQuarter = $currentQuarter;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    $issuesStmt = $conn->prepare("INSERT INTO issues_and_concerns (school_id, issues, facilitating_facts, hindering_factors, actions_taken, quarter, year, last_user_save, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($_POST['issues'] as $school_id => $value) {
        $issues = $_POST['issues'][$school_id] ?? null;
        $facilitating_factors = $_POST['facilitating_factors'][$school_id] ?? null;
        $hindering_factors = $_POST['hindering_factors'][$school_id] ?? null;
        $actions_to_be_taken = $_POST['actions_to_be_taken'][$school_id] ?? null;
        $type = 'equity';

        // Check if any of the fields have values
        if (!empty($issues) || !empty($facilitating_factors) || !empty($hindering_factors) || !empty($actions_to_be_taken)) {
            $issuesStmt->bind_param("issssiiss", $school_id, $issues, $facilitating_factors, $hindering_factors, $actions_to_be_taken, $selectedQuarter, $year, $current_user_id, $type);
            $issuesStmt->execute();
        }
    }
}

// Fetch existing data for the selected quarter and year
$existingData = [];
$existingDataQuery = "SELECT * FROM issues_and_concerns WHERE quarter = ? AND year = ? AND type = ?";
$existingDataStmt = $conn->prepare($existingDataQuery);
$type = "equity"; // Create variable to pass by reference
$existingDataStmt->bind_param("iis", $selectedQuarter, $year, $type);
$existingDataStmt->execute();
$result = $existingDataStmt->get_result();

$lastUserSave = "";
while ($row = $result->fetch_assoc()) {
    $existingData[$row['school_id']] = $row;
}

$schoolYearsql = "SELECT * FROM school_year";
$schoolYearResult = $conn->query($schoolYearsql);
$syrows = "";
if ($schoolYearResult->num_rows > 0) {
    while($row = $schoolYearResult->fetch_assoc()) {
        $syrows .= "<th scope='col'> S.Y ".$row['start_year']." - ".$row['end_year']."</th>";
    }
}

$sql = "SELECT * FROM schools";
$schools = $conn->query($sql);

$inputTables = "";

if ($schools->num_rows > 0) {
    while($row = $schools->fetch_assoc()) {
        $school_id = $row['id'];
        $issues = $existingData[$school_id]['issues'] ?? '';
        $facilitating_factors = $existingData[$school_id]['facilitating_facts'] ?? '';
        $hindering_factors = $existingData[$school_id]['hindering_factors'] ?? '';
        $actions_to_be_taken = $existingData[$school_id]['actions_taken'] ?? '';

        $inputTables .= "
            <tr>
                <td>".$row["name"]."</td>
                <td><textarea class='form-control form-control-sm auto-resize' name='issues[".$school_id."]' ".(!empty($issues) ? "readonly" : "")." ondblclick='requestEdit(this)'>".$issues."</textarea></td>
                <td><textarea class='form-control form-control-sm auto-resize' name='facilitating_factors[".$school_id."]' ".(!empty($facilitating_factors) ? "readonly" : "")." ondblclick='requestEdit(this)'>".$facilitating_factors."</textarea></td>
                <td><textarea class='form-control form-control-sm auto-resize' name='hindering_factors[".$school_id."]' ".(!empty($hindering_factors) ? "readonly" : "")." ondblclick='requestEdit(this)'>".$hindering_factors."</textarea></td>
                <td><textarea class='form-control form-control-sm auto-resize' name='actions_to_be_taken[".$school_id."]' ".(!empty($actions_to_be_taken) ? "readonly" : "")." ondblclick='requestEdit(this)'>".$actions_to_be_taken."</textarea></td>
            </tr>
        ";
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

                        .auto-resize {
                            min-height: 38px;
                            overflow: hidden;
                            resize: none;
                        }
                    </style>

                    <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">Issues and Concerns</h1>
                    <p class="mb-4">Issues and concerns on Access Pilar</p>
                    
                    <form action="equityConcerns.php" method="post">
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
                                Last Edited By: <?php echo $lastUserSave; ?>
                                <button type="submit" class="btn btn-primary" name="save">Save</button>
                            </div>
                        </div>
                    
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Name of School</th>
                                    <th scope="col">Issues and Concerns</th>
                                    <th scope="col">Facilitating Factors</th>
                                    <th scope="col">Hindering Factors</th>
                                    <th scope="col">Actions to be Taken</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo str_replace('dynamicId', 'issues', $inputTables); ?>
                            </tbody>
                        </table>
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

        function lockFields(){
            var activeTab = $('#activeTab').val().split('-')[0];
            var activeTabGrade = $('#activeTab').val().split('-')[1];
            
            $('table input').each(function() {
                // this.value = 0;
                this.readOnly = false; // Set to false to allow editing
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
                        inputBox.readOnly = true; // Set to true to prevent editing
                        updateTotal($(inputBox).closest('tr'));
                    }
                }
            }
        }
        
        function updateTotal(row) {
            // var male = parseInt(row.find('input')[0].value) || 0;
            // var female = parseInt(row.find('input')[1].value) || 0;
            // row.find('.total').text(male + female);
        }
        
        var keys = <?php echo json_encode($attendanceKeys); ?>;
        // var attendanceData = <?php echo json_encode($attendanceData); ?>;

        $(document).ready(function(){

            function clearZero(input) {
                // if (input.find('input')[0].value == '0') {
                //     input.find('input')[0].value = '';
                // } else if (input.find('input')[0].value < 1 || input.find('input')[0].value == "") {
                //     input.find('input')[0].value = '0';
                // }
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
        });

        function requestEdit(input) {
            if (input.readOnly) {
                $('#editRequestModal').modal('show');
                $('#editRequestModal').on('hidden.bs.modal', function () {
                    if ($('#confirmEditRequest').data('confirmed')) {
                        input.readOnly = false; // Enable editing
                    }
                });
            }
        }
    </script>

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1" role="dialog" aria-labelledby="editRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRequestModalLabel">Request Edit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to request an edit for this field?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEditRequest" data-confirmed="false" onclick="confirmEdit()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmEdit() {
            $('#confirmEditRequest').data('confirmed', true);
            $('#editRequestModal').modal('hide');
        }
    </script>
    <script>
        // Function to adjust textarea height
        function adjustTextareaHeight(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
        // Initialize all textareas
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.auto-resize').forEach(function(textarea) {
                adjustTextareaHeight(textarea);
                // Add input event listener for real-time adjustment
                textarea.addEventListener('input', function() {
                    adjustTextareaHeight(this);
                });
            });
        });
    </script>

</body>

</html>
