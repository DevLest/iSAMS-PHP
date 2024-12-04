<?php
ob_start();
session_start();

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
$schoolQuery = "SELECT * FROM schools WHERE id <= 17 ORDER BY name";
$schools = $conn->query($schoolQuery)->fetch_all(MYSQLI_ASSOC);

// Get grade levels
$gradeLevelQuery = "SELECT * FROM grade_level ORDER BY id";
$gradeLevels = $conn->query($gradeLevelQuery)->fetch_all(MYSQLI_ASSOC);

// Define helper functions
function saveEquityData($conn, $params) {
  try {
    $query = "SELECT id FROM equity_assessment WHERE 
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
    if (isset($params['points'])) {
      $query .= " AND points = ?";
      $values[] = $params['points'];
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
      throw new Exception("Prepare failed: " . $conn->error);
    }

    $types = str_repeat('i', count($values) - 1) . 's'; // All integers except type which is string
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
      throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      // Update
      $row = $result->fetch_assoc();
      $query = "UPDATE equity_assessment SET count = ?, last_user_save = ? WHERE id = ?";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
      }
      $stmt->bind_param('iii', $params['count'], $_SESSION['user_id'], $row['id']);
    } else {
      // Insert
      $query = "INSERT INTO equity_assessment (school_id, type, count, quarter, year, last_user_save";
      $values = [$params['school_id'], $params['type'], $params['count'], $params['quarter'], $params['year'], $_SESSION['user_id']];
      $types = 'isiiii';

      if (isset($params['grade_level'])) {
        $query .= ", grade_level";
        $values[] = $params['grade_level'];
        $types .= 'i';
      }
      if (isset($params['gender'])) {
        $query .= ", gender";
        $values[] = $params['gender'];
        $types .= 'i';
      }
      if (isset($params['points'])) {
        $query .= ", points";
        $values[] = $params['points'];
        $types .= 'i';
      }

      $query .= ") VALUES (" . str_repeat('?,', count($values)-1) . "?)";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
      }
      $stmt->bind_param($types, ...$values);
    }

    if (!$stmt->execute()) {
      throw new Exception("Execute failed: " . $stmt->error);
    }

    return true;
  } catch (Exception $e) {
    throw $e;
  }
}

function getExistingData($conn, $quarter, $year) {
  $query = "SELECT equity_assessment.*, users.first_name, users.last_name, schools.name as school_name 
            FROM equity_assessment 
            INNER JOIN users ON users.id = equity_assessment.last_user_save
            LEFT JOIN schools ON schools.id = users.school_id 
            WHERE quarter = ? AND year = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $quarter, $year);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $data = [];
  $lastUserSave = "";
  while ($row = $result->fetch_assoc()) {
    if ($row['type'] === 'cfs') {
      $data['cfs-'.$row['points']][$row['school_id']] = $row['count'];
      $data['cfs-'.$row['points']][$row['school_id'].'_editor'] = $row['last_user_save'];
    } elseif ($row['type'] === 'sbfp') {
      $gender = ($row['gender'] == 1) ? 'male' : 'female';
      $data['sbfp-'.$row['grade_level'].'-'.$gender][$row['school_id']] = $row['count'];
      $data['sbfp-'.$row['grade_level'].'-'.$gender][$row['school_id'].'_editor'] = $row['last_user_save'];
    } else {
      $data['wash-stars'][$row['school_id']] = $row['count'];
      $data['wash-stars'][$row['school_id'].'_editor'] = $row['last_user_save'];
    }
    // Set last editor info
    $lastUserSave = $row['last_name'].', '.$row['first_name'].' ('.($row['school_name'] ?? 'Admin').')';
  }
  
  return ['data' => $data, 'lastUserSave' => $lastUserSave];
}

// Get existing data
$existingDataResult = getExistingData($conn, $selectedQuarter, $year);
$existingData = $existingDataResult['data'];
$lastUserSave = $existingDataResult['lastUserSave'];

// Add this after getting existing data
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  $successCount = 0;
  $errors = [];
  
  // Process CFS data
  foreach (['25', '30', '35'] as $points) {
    if (isset($_POST["cfs-$points"])) {
      foreach ($_POST["cfs-$points"] as $schoolId => $count) {
        // Skip if empty, null, or zero
        if ($count === '' || $count === null || $count === '0') {
          continue;
        }
        
        try {
          saveEquityData($conn, [
            'school_id' => $schoolId,
            'type' => 'cfs',
            'points' => $points,
            'count' => $count,
            'quarter' => $selectedQuarter,
            'year' => $year
          ]);
          $successCount++;
        } catch (Exception $e) {
          $errors[] = "Error saving CFS data: " . $e->getMessage();
        }
      }
    }
  }

  // Process SBFP data
  foreach ($gradeLevels as $level) {
    foreach (['male', 'female'] as $gender) {
      if (isset($_POST["sbfp-{$level['id']}-$gender"])) {
        foreach ($_POST["sbfp-{$level['id']}-$gender"] as $schoolId => $count) {
          // Skip if empty, null, or zero
          if ($count === '' || $count === null || $count === '0') {
            continue;
          }
          
          try {
            saveEquityData($conn, [
              'school_id' => $schoolId,
              'grade_level' => $level['id'],
              'gender' => $gender === 'male' ? 1 : 2,
              'type' => 'sbfp',
              'count' => $count,
              'quarter' => $selectedQuarter,
              'year' => $year
            ]);
            $successCount++;
          } catch (Exception $e) {
            $errors[] = "Error saving SBFP data: " . $e->getMessage();
          }
        }
      }
    }
  }

  // Process WASH data
  if (isset($_POST['wash-stars'])) {
    foreach ($_POST['wash-stars'] as $schoolId => $stars) {
      // Skip if empty, null, or zero
      if ($stars === '' || $stars === null || $stars === '0') {
        continue;
      }
      
      try {
        saveEquityData($conn, [
          'school_id' => $schoolId,
          'type' => 'wash',
          'count' => $stars,
          'quarter' => $selectedQuarter,
          'year' => $year
        ]);
        $successCount++;
      } catch (Exception $e) {
        $errors[] = "Error saving WASH data: " . $e->getMessage();
      }
    }
  }

  // Set success/error messages
  if ($successCount > 0) {
    $_SESSION['success'] = "Successfully saved $successCount record(s).";
  }
  if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
  }

  // Redirect to prevent form resubmission
  header("Location: equityAdd.php?quarter=$selectedQuarter&year=$year");
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Equity Assessment - SMEA</title>
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
          <h1 class="h3 mb-2 text-gray-800">Equity Assessment</h1>
          <p class="mb-4">Enter equity assessment data for analysis and comparison.</p>

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

          <form action="equityAdd.php" method="post">
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
                    <a class="nav-link active" data-toggle="pill" href="#cfs" role="tab">Child-Friendly School</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#sbfp" role="tab">School-based Feeding Program</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#wash" role="tab">Water Sanitation</a>
                  </li>
                </ul>
              </div>

              <div class="card-body">
                <div class="tab-content">
                  <!-- Child-Friendly School Tab -->
                  <div class="tab-pane fade show active" id="cfs" role="tabpanel">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>School Name</th>
                          <th>Child-Friendly School (25 Points)</th>
                          <th>Outstanding Child-Friendly School (30 Points)</th>
                          <th>Very Outstanding Child-Friendly School (35+ Points)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($schools as $school): ?>
                        <tr>
                          <td><?php echo $school['name']; ?></td>
                          <td>
                            <input type="number" class="form-control form-control-sm" 
                                   name="cfs-25[<?php echo $school['id']; ?>]" 
                                   value="<?php echo isset($existingData['cfs-25'][$school['id']]) ? $existingData['cfs-25'][$school['id']] : '0'; ?>">
                          </td>
                          <td>
                            <input type="number" class="form-control form-control-sm" 
                                   name="cfs-30[<?php echo $school['id']; ?>]" 
                                   value="<?php echo isset($existingData['cfs-30'][$school['id']]) ? $existingData['cfs-30'][$school['id']] : '0'; ?>">
                          </td>
                          <td>
                            <input type="number" class="form-control form-control-sm" 
                                   name="cfs-35[<?php echo $school['id']; ?>]" 
                                   value="<?php echo isset($existingData['cfs-35'][$school['id']]) ? $existingData['cfs-35'][$school['id']] : '0'; ?>">
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- SBFP Tab -->
                  <div class="tab-pane fade" id="sbfp" role="tabpanel">
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
                                           name="sbfp-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['sbfp-'.$level['id'].'-male'][$school['id']]) ? $existingData['sbfp-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="sbfp-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['sbfp-'.$level['id'].'-female'][$school['id']]) ? $existingData['sbfp-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
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

                  <!-- Water Sanitation Tab -->
                  <div class="tab-pane fade" id="wash" role="tabpanel">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>School Name</th>
                          <th>Number of Stars</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($schools as $school): ?>
                        <tr>
                          <td><?php echo $school['name']; ?></td>
                          <td>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   name="wash-stars[<?php echo $school['id']; ?>]"
                                   min="0"
                                   max="5"
                                   value="<?php echo isset($existingData['wash-stars'][$school['id']]) ? $existingData['wash-stars'][$school['id']] : '0'; ?>">
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
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
  // Add these at the beginning of your script section
  var existingData = <?php echo json_encode($existingDataResult['data']); ?>;
  var role = <?php echo $_SESSION['role']; ?>;
  var currentUserId = <?php echo $_SESSION['user_id']; ?>;
  var editPermissions = <?php echo json_encode($editPermissions); ?>;

  $(document).ready(function() {
    // Remove any activeTab related code
    
    function lockFields() {
        // First enable all fields by default
        $('input[type="number"]').prop('readonly', false);
        
        // Lock only specific fields
        $('input[type="number"]').each(function() {
            var $input = $(this);
            var inputName = $input.attr('name');
            var value = parseInt($input.val()) || 0;
            
            // Only process fields that have data (value > 0)
            if (value > 0) {
                if (inputName) {
                    var matches;
                    var type, points, gender, schoolId;
                    var lastEditorId = null;
                    
                    // Parse input name based on type
                    if (inputName.startsWith('cfs-')) {
                        matches = inputName.match(/cfs-(\d+)\[(\d+)\]/);
                        if (matches) {
                            type = 'cfs';
                            points = matches[1];
                            schoolId = matches[2];
                            var permissionKey = type + '-0-0-' + schoolId;
                            // Get last editor ID from the data
                            lastEditorId = existingData['cfs-' + points] && 
                                         existingData['cfs-' + points][schoolId + '_editor'];
                        }
                    } else if (inputName.startsWith('sbfp-')) {
                        matches = inputName.match(/sbfp-(\d+)-(\w+)\[(\d+)\]/);
                        if (matches) {
                            type = 'sbfp';
                            var gradeLevel = matches[1];
                            gender = matches[2] === 'male' ? '1' : '2';
                            schoolId = matches[3];
                            var permissionKey = type + '-' + gradeLevel + '-' + gender + '-' + schoolId;
                            // Get last editor ID from the data
                            lastEditorId = existingData['sbfp-' + gradeLevel + '-' + matches[2]] && 
                                         existingData['sbfp-' + gradeLevel + '-' + matches[2]][schoolId + '_editor'];
                        }
                    } else if (inputName.startsWith('wash-')) {
                        matches = inputName.match(/wash-stars\[(\d+)\]/);
                        if (matches) {
                            type = 'wash';
                            schoolId = matches[1];
                            var permissionKey = type + '-0-0-' + schoolId;
                            // Get last editor ID from the data
                            lastEditorId = existingData['wash-stars'] && 
                                         existingData['wash-stars'][schoolId + '_editor'];
                        }
                    }

                    console.log('Input:', inputName, 'LastEditor:', lastEditorId, 'CurrentUser:', currentUserId); // Debug line

                    // Admin can always edit
                    if (role === 1) {
                        $input.prop('readonly', false);
                    }
                    // Last editor can edit their own entries
                    else if (lastEditorId === currentUserId) {
                        $input.prop('readonly', false);
                    }
                    // Check edit permissions for other users
                    else {
                        var permission = editPermissions[permissionKey];
                        console.log(permissionKey, permission); // Debug line
                        
                        if (permission === 'approved') {
                            $input.prop('readonly', false);
                            $input.addClass('approved-edit');
                            $input.attr('title', 'Edit permission approved');
                        } else if (permission === 'pending') {
                            $input.prop('readonly', true);
                            $input.addClass('pending-edit');
                            $input.attr('title', 'Edit permission pending approval');
                        } else {
                            // Only lock if there's data and it's not the current user's entry
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

    // Handle tab changes without activeTab
    $('.nav-link').on('click', function() {
        setTimeout(lockFields, 100);
    });

    // Handle double-click on readonly inputs
    $(document).on('dblclick', 'input[type="number"]', function() {
        if ($(this).prop('readonly')) {
            const $input = $(this);
            const inputName = $input.attr('name');
            let type, points, gender, gradeLevel, schoolId;
            
            if (inputName.startsWith('cfs-')) {
                const matches = inputName.match(/cfs-(\d+)\[(\d+)\]/);
                if (matches) {
                    type = 'cfs';
                    points = matches[1];
                    schoolId = matches[2];
                    gradeLevel = '0';
                    gender = '0';
                }
            } else if (inputName.startsWith('sbfp-')) {
                const matches = inputName.match(/sbfp-(\d+)-(\w+)\[(\d+)\]/);
                if (matches) {
                    type = 'sbfp';
                    gradeLevel = matches[1];
                    gender = matches[2] === 'male' ? '1' : '2';
                    schoolId = matches[3];
                    points = '0';
                }
            } else if (inputName.startsWith('wash-')) {
                const matches = inputName.match(/wash-stars\[(\d+)\]/);
                if (matches) {
                    type = 'wash';
                    schoolId = matches[1];
                    gradeLevel = '0';
                    gender = '0';
                    points = '0';
                }
            }

            if (schoolId) {
                $('#requestSchoolId').val(schoolId);
                $('#requestType').val(type);
                $('#requestGradeLevel').val(gradeLevel);
                $('#requestGender').val(gender);
                $('#requestPoints').val(points);
                
                $('#editRequestModal').modal('show');
            }
        }
    });

    // Handle edit request submission
    $('#submitEditRequest').on('click', function() {
        const formData = {
            school_id: $('#requestSchoolId').val(),
            type: $('#requestType').val(),
            grade_level: $('#requestGradeLevel').val(),
            gender: $('#requestGender').val(),
            points: $('#requestPoints').val(),
            reason: $('#requestReason').val()
        };

        $.ajax({
            url: 'requestEdit.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                const result = JSON.parse(response);
                $('#editRequestForm').hide();
                $('#editRequestStatus').show();
                $('#editRequestStatus .alert')
                    .removeClass('alert-danger alert-success')
                    .addClass('alert-success')
                    .text(result.message);
                
                $('#submitEditRequest').hide();
            },
            error: function() {
                $('#editRequestStatus').show();
                $('#editRequestStatus .alert')
                    .removeClass('alert-danger alert-success')
                    .addClass('alert-danger')
                    .text('An error occurred. Please try again.');
            }
        });
    });

    // Reset modal when closed
    $('#editRequestModal').on('hidden.bs.modal', function () {
        $('#editRequestForm').show();
        $('#editRequestStatus').hide();
        $('#submitEditRequest').show();
        $('#requestReason').val('');
    });

    // Update totals on input change
    $('input[type="number"]').on('input', function() {
        updateTotal($(this).closest('tr'));
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
                      <input type="hidden" id="requestPoints" name="points">
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
</html>