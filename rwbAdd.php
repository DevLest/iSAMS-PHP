<?php
ob_start();
session_start();

ini_set('max_input_vars', 10000);

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

require_once "connection/db.php";
include_once('header.php');

// Get selected quarter and year
$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);
$selectedQuarter = isset($_GET['quarter']) ? $_GET['quarter'] : (isset($_POST['quarter']) ? $_POST['quarter'] : $currentQuarter);
$year = isset($_GET['year']) ? $_GET['year'] : (isset($_POST['year']) ? $_POST['year'] : date('Y'));

// Get school years for dropdown
$schoolYearQuery = "SELECT * FROM school_year ORDER BY start_year DESC";
$schoolYears = $conn->query($schoolYearQuery)->fetch_all(MYSQLI_ASSOC);

// Get schools
$schoolQuery = "SELECT * FROM schools";
$schools = $conn->query($schoolQuery)->fetch_all(MYSQLI_ASSOC);

// Get grade levels
$gradeLevelQuery = "SELECT * FROM grade_level ORDER BY id";
$gradeLevels = $conn->query($gradeLevelQuery)->fetch_all(MYSQLI_ASSOC);

// Define helper functions
function saveRwbData($conn, $params) {
  try {    
    $query = "SELECT id FROM rwb_assessment WHERE 
              school_id = ? AND type = ? AND quarter = ? AND year = ?";
    
    $values = [$params['school_id'], $params['type'], $params['quarter'], $params['year']];
    
    if (isset($params['grade_level'])) {
      $query .= " AND grade_level = ?";
      $values[] = $params['grade_level'];
    }
    if (isset($params['gender'])) {
      $query .= " AND gender = ?";
      $values[] = $params['gender'];
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
      throw new Exception("Prepare failed: " . $conn->error);
    }

    $types = 'isiiii'; // All integers except type which is string
    
    // Debug output
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
      throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    

    if ($result->num_rows > 0) {
        // Record exists - UPDATE
        $row = $result->fetch_assoc();
        $updateQuery = "UPDATE rwb_assessment SET count = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ii', $params['count'], $row['id']);
        
        $updateStmt->execute();
        error_log("Updated RWB record with ID: " . $row['id']);
    } else {
        // No record - INSERT
        $insertQuery = "INSERT INTO rwb_assessment (school_id, grade_level, gender, type, count, quarter, year, last_user_save) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('iiisiiis', 
            $params['school_id'],
            $params['grade_level'],
            $params['gender'],
            $params['type'],
            $params['count'],
            $params['quarter'],
            $params['year'],
            $_SESSION['user_id']
        );
        $insertStmt->execute();
        error_log("Inserted new RWB record with ID: " . $conn->insert_id);
    }

    return true;
  } catch (Exception $e) {
    error_log("Error in saveRwbData: " . $e->getMessage());
    return false;
  }

}
function getExistingData($conn, $quarter, $year) {
  $query = "SELECT rwb_assessment.*, users.first_name, users.last_name, schools.name as school_name 
            FROM rwb_assessment 
            INNER JOIN users ON users.id = rwb_assessment.last_user_save
            LEFT JOIN schools ON schools.id = users.school_id 
            WHERE quarter = ? AND year = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $quarter, $year);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $data = [];
  $lastUserSave = "";
  while ($row = $result->fetch_assoc()) {
    // if ($row['type'] === 'bmi') {
      $gender = ($row['gender'] == 1) ? 'male' : 'female';
      $data[$row['type'].'-'.$row['grade_level'].'-'.$gender][$row['school_id']] = $row['count'];
    // }
    // Set last editor info
    $lastUserSave = $row['last_name'].', '.$row['first_name'].' ('.($row['school_name'] ?? 'Admin').')';
  }
  
  return ['data' => $data, 'lastUserSave' => $lastUserSave];
}

// Get existing data
$existingDataResult = getExistingData($conn, $selectedQuarter, $year);
$tableData = $existingDataResult['data'];
$lastUserSave = $existingDataResult['lastUserSave'] ?? '';

// Get edit permissions
$editRequestsQuery = "SELECT school_id, type, grade_level, gender, status 
                     FROM edit_requests 
                     WHERE requested_by = {$_SESSION['user_id']} 
                     AND status IN ('pending', 'approved')";
$editRequestsResult = $conn->query($editRequestsQuery);
$editPermissions = [];

while ($row = $editRequestsResult->fetch_assoc()) {
    $key = "{$row['type']}-{$row['grade_level']}-{$row['gender']}-{$row['school_id']}";
    $editPermissions[$key] = $row['status'];
}

// Make sure these variables are available to JavaScript
echo "<script>
    var tableData = ".json_encode($tableData).";
    var currentUserId = ".$_SESSION['user_id'].";
    var role = ".$_SESSION['role'].";
    var editPermissions = ".json_encode($editPermissions).";
</script>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  $successCount = 0;
  $errors = [];

  // Process each tab's data
  $types = ['displaced', 'bullying', 'equipped', 'bmi'];
  
  // Add error checking for max_input_vars
  if (count($_POST, COUNT_RECURSIVE) >= ini_get('max_input_vars')) {
    $_SESSION['error'] = "Form submission exceeded maximum allowed inputs. Please try submitting fewer changes at once.";
    header("Location: rwbAdd.php?quarter=$selectedQuarter&year=$year");
    exit();
  }
  
  foreach ($types as $type) {
    foreach ($gradeLevels as $level) {
      foreach (['male', 'female'] as $gender) {
        $key = "$type-{$level['id']}-$gender";
        if (isset($_POST[$key])) {
          foreach ($_POST[$key] as $schoolId => $count) {
            if ($count === '' || $count === null || $count === '0') {
              continue;
            }
            
            try {
              saveRwbData($conn, [
                'school_id' => $schoolId,
                'grade_level' => $level['id'],
                'gender' => $gender === 'male' ? 1 : 2,
                'type' => $type,
                'count' => $count,
                'quarter' => $selectedQuarter,
                'year' => $year
              ]);
              $successCount++;
            } catch (Exception $e) {
              $errors[] = "Error saving $type data: " . $e->getMessage();
            }
          }
        }
      }
    }
  }

  // Set messages and redirect
  if ($successCount > 0) {
    $_SESSION['success'] = "Successfully saved $successCount record(s).";
  }
  if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
  }

  header("Location: rwbAdd.php?quarter=$selectedQuarter&year=$year");
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Welfare and Behavior Assessment - SMEA</title>
  <style>
    .approved-edit {
        background-color: #e8f5e9 !important;
        border-color: #4caf50 !important;
    }

    .pending-edit {
        background-color: #fff3e0 !important;
        border-color: #ff9800 !important;
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
</head>

<body id="page-top">
  <!-- Page Wrapper -->
  <div id="wrapper">
    <?php include_once "sidebar.php"; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
      <!-- Main Content -->
      <div id="content">
        <?php include_once "navbar.php"; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
          <!-- Page Heading -->
          <h1 class="h3 mb-2 text-gray-800">Welfare and Behavior Assessment</h1>
          <p class="mb-4">Enter welfare and behavior assessment data for analysis and comparison.</p>

          <!-- Add this after your page heading -->
          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php 
              echo $_SESSION['success'];
              unset($_SESSION['success']);
              ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php 
              echo $_SESSION['error'];
              unset($_SESSION['error']);
              ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <form action="rwbAdd.php" method="post">
            <!-- Quarter/Year Filter -->
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
                <select id="year" name="year" class="form-control d-inline-block w-auto mr-2">
                  <?php foreach ($schoolYears as $sy): ?>
                    <option value="<?php echo $sy['end_year']; ?>" <?php echo $year == $sy['end_year'] ? 'selected' : ''; ?>>
                      SY <?php echo $sy['start_year']."-".$sy['end_year']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary" name="filter">Filter</button>
              </div>
              <div class="col-md-6 text-right">
                Last Edited By: <?php echo $lastUserSave; ?>
                <button type="submit" class="btn btn-primary" name="save">Save Changes</button>
              </div>
            </div>

            <!-- Main Card -->
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#displaced" role="tab">Displaced Learner</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#bullying" role="tab">Bullying and Child Abuse</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#equipped" role="tab">Equipped Learners</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#bmi" role="tab">Learners with 80% BMI</a>
                  </li>
                </ul>
              </div>

              <div class="card-body">
                <div class="tab-content">
                  <!-- Displaced Learner Tab -->
                  <div class="tab-pane fade show active" id="displaced" role="tabpanel">
                    <div class="row">
                      <div class="col-md-3">
                        <div class="nav flex-column nav-pills">
                          <?php foreach ($gradeLevels as $level): ?>
                          <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>" 
                             data-toggle="pill" 
                             href="#displaced-grade-<?php echo $level['id']; ?>" 
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
                               id="displaced-grade-<?php echo $level['id']; ?>" 
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
                                <?php foreach ($schools as $school): ?>
                                <tr>
                                  <td><?php echo $school['name']; ?></td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="displaced-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData["displaced-{$level['id']}-male"][$school['id']]) ? $tableData["displaced-{$level['id']}-male"][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="displaced-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData["displaced-{$level['id']}-female"][$school['id']]) ? $tableData["displaced-{$level['id']}-female"][$school['id']] : '0'; ?>">
                                  </td>
                                  <td class="total">0</td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Bullying Tab -->
                  <div class="tab-pane fade" id="bullying" role="tabpanel">
                    <div class="row">
                      <div class="col-md-3">
                        <div class="nav flex-column nav-pills">
                          <?php foreach ($gradeLevels as $level): ?>
                          <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>" 
                             data-toggle="pill" 
                             href="#grade-<?php echo $level['id']; ?>" 
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
                               id="grade-<?php echo $level['id']; ?>" 
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
                                <?php foreach ($schools as $school): ?>
                                <tr>
                                  <td><?php echo $school['name']; ?></td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bullying-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['bullying-'.$level['id'].'-male'][$school['id']]) ? $tableData['bullying-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bullying-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['bullying-'.$level['id'].'-female'][$school['id']]) ? $tableData['bullying-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td class="total">0</td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Equipped Tab -->
                  <div class="tab-pane fade" id="equipped" role="tabpanel">
                    <div class="row">
                      <div class="col-md-3">
                        <div class="nav flex-column nav-pills">
                          <?php foreach ($gradeLevels as $level): ?>
                          <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>" 
                             data-toggle="pill" 
                             href="#equipped-grade-<?php echo $level['id']; ?>" 
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
                               id="equipped-grade-<?php echo $level['id']; ?>" 
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
                                <?php foreach ($schools as $school): ?>
                                <tr>
                                  <td><?php echo $school['name']; ?></td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="equipped-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['equipped-'.$level['id'].'-male'][$school['id']]) ? $tableData['equipped-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="equipped-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['equipped-'.$level['id'].'-female'][$school['id']]) ? $tableData['equipped-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td class="total">0</td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- BMI Tab -->
                  <div class="tab-pane fade" id="bmi" role="tabpanel">
                    <div class="row">
                      <div class="col-md-3">
                        <div class="nav flex-column nav-pills">
                          <?php foreach ($gradeLevels as $level): ?>
                          <a class="nav-link <?php echo $level['id'] == 1 ? 'active' : ''; ?>" 
                             data-toggle="pill" 
                             href="#bmi-grade-<?php echo $level['id']; ?>" 
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
                               id="bmi-grade-<?php echo $level['id']; ?>" 
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
                                <?php foreach ($schools as $school): ?>
                                <tr>
                                  <td><?php echo $school['name']; ?></td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bmi-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['bmi-'.$level['id'].'-male'][$school['id']]) ? $tableData['bmi-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bmi-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($tableData['bmi-'.$level['id'].'-female'][$school['id']]) ? $tableData['bmi-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td class="total">0</td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
      <?php include_once "footer.php"; ?>
    </div>
  </div>

  <script>
  var existingData = <?php echo json_encode($existingDataResult['data']); ?>;
  var role = <?php echo $_SESSION['role']; ?>;
  var currentUserId = <?php echo $_SESSION['user_id']; ?>;
  var editPermissions = <?php echo json_encode($editPermissions); ?>;

  $(document).ready(function() {
    function lockFields() {

        // First enable all fields by default
        $('input[type="number"]').prop('readonly', false);
        $('input[type="number"]').removeClass('approved-edit pending-edit');
        
        // Lock fields based on last editor and permissions
        $('input[type="number"]').each(function() {
            var $input = $(this);
            var inputName = $input.attr('name');
            var value = parseInt($input.val()) || 0;
            
            // Only process fields that have data (value > 0)
            if (value > 0) {
                var matches = inputName.match(/(\w+)-(\d+)-(\w+)\[(\d+)\]/);
                if (matches) {
                    var type = matches[1];          // displaced, bullying, equipped, or bmi
                    var gradeLevel = matches[2];    // grade level id
                    var gender = matches[3];        // male or female
                    var schoolId = matches[4];      // school id
                    var genderCode = gender === 'male' ? '1' : '2';

                    // Get the last editor ID from the data structure
                    var dataKey = type + '-' + gradeLevel + '-' + gender;
                    var permissionKey = type + '-' + gradeLevel + '-' + genderCode + '-' + schoolId;
                    
                    // Check if current user is the last editor
                    var lastEditorId = null;
                    if (tableData[dataKey] && tableData[dataKey][schoolId + '_editor']) {
                        lastEditorId = tableData[dataKey][schoolId + '_editor'];
                    }
                    var isLastEditor = lastEditorId === currentUserId;

                    // Admin can always edit
                    if (role === 1) {
                        $input.prop('readonly', false);
                    }
                    // Last editor can edit their own entries
                    else if (isLastEditor) {
                        $input.prop('readonly', false);
                    }
                    // Check edit permissions for other users
                    else {
                        var permission = editPermissions[permissionKey];
                        
                        if (permission === 'approved') {
                            $input.prop('readonly', false);
                            $input.addClass('approved-edit');
                            $input.attr('title', 'Edit permission approved');
                        } else if (permission === 'pending') {
                            $input.prop('readonly', true);
                            $input.addClass('pending-edit');
                            $input.attr('title', 'Edit permission pending approval');
                        } else {
                            $input.prop('readonly', true);
                            $input.attr('title', 'Double-click to request edit permission');
                        }
                    }
                }
            }
        });
    }

    // Initialize fields on page load
    lockFields();

    // Handle tab changes
    $('.nav-link').on('click', function() {
        setTimeout(lockFields, 100);
    });

    // Handle double-click on readonly inputs
    $(document).on('dblclick', 'input[type="number"]', function() {
        if ($(this).prop('readonly')) {
            const $input = $(this);
            const inputName = $input.attr('name');
            const matches = inputName.match(/(\w+)-(\d+)-(\w+)\[(\d+)\]/);
            
            if (matches) {
                const type = matches[1];
                const gradeLevel = matches[2];
                const gender = matches[3] === 'male' ? '1' : '2';
                const schoolId = matches[4];

                $('#requestSchoolId').val(schoolId);
                $('#requestType').val(type);
                $('#requestGradeLevel').val(gradeLevel);
                $('#requestGender').val(gender);
                
                $('#editRequestModal').modal('show');
            }
        }
    });

    // Remove the automatic form submission on select change
    $('#quarter, #year').off('change');

    // Update totals function
    function updateTotal(row) {
      var male = parseInt(row.find('input[name*="male"]').val()) || 0;
      var female = parseInt(row.find('input[name*="female"]').val()) || 0;
      row.find('.total').text(male + female);
    }

    // Initialize totals
    $('tr').each(function() {
      if ($(this).find('input[type="number"]').length) {
        updateTotal($(this));
      }
    });

    // Update totals on input change
    $('input[type="number"]').on('input', function() {
      updateTotal($(this).closest('tr'));
    });

    // Handle quarter/year changes
    $('#quarter, #year').on('change', function() {
      $(this).closest('form').submit();
    });

    // Clear zero values on focus
    $('input[type="number"]').on('focus', function() {
      if ($(this).val() == '0') {
        $(this).val('');
      }
    });

    // Reset to zero if empty on blur
    $('input[type="number"]').on('blur', function() {
      if ($(this).val() === '') {
        $(this).val('0');
      }
    });

    // Validate WASH stars (0-5)
    $('input[name^="wash-stars"]').on('input', function() {
      var value = parseInt($(this).val());
      if (value > 5) {
        $(this).val(5);
      } else if (value < 0) {
        $(this).val(0);
      }
    });

    // Handle edit request submission
    $('#submitEditRequest').on('click', function() {
        // Get form data
        const formData = {
            school_id: $('#requestSchoolId').val(),
            type: $('#requestType').val(),
            grade_level: $('#requestGradeLevel').val(),
            gender: $('#requestGender').val(),
            reason: $('#requestReason').val()
        };

        console.log('Submitting edit request:', formData); // Debug log

        // Validate required fields
        if (!formData.reason) {
            alert('Please provide a reason for the edit request');
            return;
        }

        // Disable submit button to prevent double submission
        $('#submitEditRequest').prop('disabled', true);

        // Send AJAX request
        $.ajax({
            url: 'requestEdit.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('Response received:', response); // Debug log
                
                try {
                    const result = JSON.parse(response);
                    $('#editRequestForm').hide();
                    $('#editRequestStatus').show();
                    
                    if (result.success) {
                        $('#editRequestStatus .alert')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .text(result.message || 'Edit request submitted successfully');
                        
                        // Update permissions and refresh field states
                        if (editPermissions) {
                            const permissionKey = `${formData.type}-${formData.grade_level}-${formData.gender}-${formData.school_id}`;
                            editPermissions[permissionKey] = 'pending';
                            lockFields();
                        }
                    } else {
                        $('#editRequestStatus .alert')
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .text(result.message || 'Failed to submit edit request');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    $('#editRequestStatus .alert')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text('An error occurred while processing the response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error); // Debug log
                $('#editRequestStatus').show();
                $('#editRequestStatus .alert')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .text('An error occurred while submitting the request. Please try again.');
            },
            complete: function() {
                // Re-enable submit button
                $('#submitEditRequest').prop('disabled', false);
            }
        });
    });

    // Reset modal when closed
    $('#editRequestModal').on('hidden.bs.modal', function () {
        $('#editRequestForm').show();
        $('#editRequestStatus').hide();
        $('#submitEditRequest').prop('disabled', false);
        $('#requestReason').val('');
        $('#editRequestStatus .alert').removeClass('alert-success alert-danger').text('');
    });
  });
  </script>

  <!-- Add the edit request modal -->
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
</body>
</html></html>
