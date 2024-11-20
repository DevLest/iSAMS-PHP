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

    $types = str_repeat('i', count($values) - 1) . 's'; // All integers except type which is string
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
      throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      // Update
      $row = $result->fetch_assoc();
      $query = "UPDATE rwb_assessment SET count = ?, last_user_save = ? WHERE id = ?";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
      }
      $stmt->bind_param('iii', $params['count'], $_SESSION['user_id'], $row['id']);
    } else {
      // Insert
      $query = "INSERT INTO rwb_assessment (school_id, type, count, quarter, year, last_user_save";
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
    if ($row['type'] === 'bmi') {
      $gender = ($row['gender'] == 1) ? 'male' : 'female';
      $data['bmi-'.$row['grade_level'].'-'.$gender][$row['school_id']] = $row['count'];
    }
    // Set last editor info
    $lastUserSave = $row['last_name'].', '.$row['first_name'].' ('.($row['school_name'] ?? 'No School').')';
  }
  
  return ['data' => $data, 'lastUserSave' => $lastUserSave];
}

// Get existing data
$existingData = getExistingData($conn, $selectedQuarter, $year);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  $successCount = 0;
  $errors = [];
  
  // Process each tab's data
  $types = ['displaced', 'bullying', 'equipped', 'bmi'];
  
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
  <title>Equity Assessment - SMEA</title>
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
                                           value="<?php echo isset($existingData["displaced-{$level['id']}-male"][$school['id']]) ? $existingData["displaced-{$level['id']}-male"][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="displaced-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData["displaced-{$level['id']}-female"][$school['id']]) ? $existingData["displaced-{$level['id']}-female"][$school['id']] : '0'; ?>">
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
                                           value="<?php echo isset($existingData['bullying-'.$level['id'].'-male'][$school['id']]) ? $existingData['bullying-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bullying-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['bullying-'.$level['id'].'-female'][$school['id']]) ? $existingData['bullying-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
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
                                           name="equipped-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['equipped-'.$level['id'].'-male'][$school['id']]) ? $existingData['equipped-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="equipped-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['equipped-'.$level['id'].'-female'][$school['id']]) ? $existingData['equipped-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
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
                                           name="bmi-<?php echo $level['id']; ?>-male[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['bmi-'.$level['id'].'-male'][$school['id']]) ? $existingData['bmi-'.$level['id'].'-male'][$school['id']] : '0'; ?>">
                                  </td>
                                  <td>
                                    <input type="number" class="form-control form-control-sm"
                                           name="bmi-<?php echo $level['id']; ?>-female[<?php echo $school['id']; ?>]"
                                           value="<?php echo isset($existingData['bmi-'.$level['id'].'-female'][$school['id']]) ? $existingData['bmi-'.$level['id'].'-female'][$school['id']] : '0'; ?>">
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
  $(document).ready(function() {
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
  });
  </script>
</body>
</html>
</html>