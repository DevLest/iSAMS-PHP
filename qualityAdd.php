<?php
ob_start();
session_start();

if(!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once "connection/db.php";
include_once('header.php');

// Get schools for input tables
$schoolQuery = "SELECT * FROM schools ORDER BY id";
$schoolResult = $conn->query($schoolQuery);
$inputTables = "";

if ($schoolResult->num_rows > 0) {
  while($row = $schoolResult->fetch_assoc()) {
    $inputTables .= '
      <tr>
        <td>'.$row["name"].'</td>
        <td><input type="number" min="0" class="form-control form-control-sm" name="dynamicId-male['.$row["id"].']" value="0"></td>
        <td><input type="number" min="0" class="form-control form-control-sm" name="dynamicId-female['.$row["id"].']" value="0"></td>
        <td class="total">0</td>
      </tr>';
  }
}

// Get grade levels
$grade_levels = "";
$grade_level_inputs = "";
$sql = "SELECT * FROM grade_level ORDER BY id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    // Build grade level navigation pills
    $grade_levels .= '
      <a class="nav-link '.($row["id"] == 1 ? 'active' : '').'" 
         id="v-pills-dynamicId-'.$row["id"].'-tab" 
         data-toggle="pill" 
         href="#v-pills-dynamicId-'.$row["id"].'" 
         role="tab" 
         onclick="activeTab(\'dynamicId-'.$row["id"].'\')">'.$row["name"].'</a>';

    // Build grade level content areas
    $grade_level_inputs .= '
      <div class="tab-pane fade '.($row["id"] == 1 ? 'show active' : '').'" 
           id="v-pills-dynamicId-'.$row["id"].'" 
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
            '.str_replace('dynamicId', 'dynamicId-'.$row["id"], $inputTables).'
          </tbody>
        </table>
      </div>';
  }
}

$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);
$year = date('Y');

if (isset($_POST['quarter'])) {
  $selectedQuarter = $_POST['quarter'];
} else {
  $selectedQuarter = $currentQuarter;
}

// Save functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  if (!isset($_POST['activeTab'])) {
    exit;
  }

  $current_user_id = $_SESSION['user_id'];
  $parts = explode('-', $_POST['activeTab']);
  if (count($parts) == 3) {
    list($subject, $type, $grade_level) = $parts;
    $type = $subject . '-' . $type;
  } else {
    list($type, $grade_level) = $parts;
  }
  
  // Function to handle database operations
  function saveData($conn, $params) {
    $query = sprintf(
      "SELECT * FROM quality_assessment WHERE school_id = '%s' AND type = '%s' AND quarter = '%s' AND year = '%s'",
      mysqli_real_escape_string($conn, $params['school_id']),
      mysqli_real_escape_string($conn, $params['type']), 
      mysqli_real_escape_string($conn, $params['quarter']),
      mysqli_real_escape_string($conn, $params['year'])
    );
    
    // Add optional conditions for non-ALS entries
    if (isset($params['grade_level'])) {
      $query .= sprintf(" AND grade_level = '%s'", mysqli_real_escape_string($conn, $params['grade_level']));
    }
    if (isset($params['gender'])) {
      $query .= sprintf(" AND gender = '%s'", mysqli_real_escape_string($conn, $params['gender']));
    }
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
      // Update
      $query = "UPDATE quality_assessment SET count = ?, last_user_save = ? WHERE school_id = ? AND type = ? AND quarter = ? AND year = ?";
      $values = [$params['count'], $params['user_id'], $params['school_id'], $params['type'], $params['quarter'], $params['year']];
      
      if (isset($params['grade_level'])) {
        $query .= " AND grade_level = ?";
        $values[] = $params['grade_level'];
      }
      if (isset($params['gender'])) {
        $query .= " AND gender = ?";
        $values[] = $params['gender'];
      }
    } else {
      // Insert
      $fields = ['school_id', 'type', 'count', 'quarter', 'year', 'last_user_save'];
      $values = [$params['school_id'], $params['type'], $params['count'], $params['quarter'], $params['year'], $params['user_id']];
      
      if (isset($params['grade_level'])) {
        $fields[] = 'grade_level';
        $values[] = $params['grade_level'];
      }
      if (isset($params['gender'])) {
        $fields[] = 'gender';
        $values[] = $params['gender'];
      }
      
      $query = "INSERT INTO quality_assessment (" . implode(", ", $fields) . ") VALUES (" . str_repeat("?,", count($fields)-1) . "?)";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat("s", count($values)), ...$values);
    return $stmt->execute();
  }

  // Handle ALS tab
  if ($type === 'als') {
    $als_types = ['blp', 'elementary', 'jhs'];
    foreach ($als_types as $als_type) {
      if (isset($_POST['als-'.$als_type])) {
        foreach ($_POST['als-'.$als_type] as $school_id => $count) {
          if (!empty($count) && $count > 0) {
            saveData($conn, [
              'school_id' => $school_id,
              'type' => "als",
              'count' => $count,
              'quarter' => $_POST['quarter'],
              'year' => $year,
              'user_id' => $current_user_id,
              'grade_level' => $als_type == 'blp' ? 1 : ($als_type == 'elementary' ? 2 : 3),
              'gender' => 1
            ]);
          }
        }
      }
    }
  } else {
    $genders = ['male' => 1, 'female' => 2];
    foreach ($genders as $gender_type => $gender_value) {
      if (isset($_POST[$type.'-'.$gender_type])) {
        foreach ($_POST[$type.'-'.$gender_type] as $school_id => $count) {
          if (!empty($count) && $count > 0) {
            saveData($conn, [
              'school_id' => $school_id,
              'type' => $type,
              'count' => $count,
              'quarter' => $_POST['quarter'],
              'year' => $year,
              'user_id' => $current_user_id,
              'grade_level' => $grade_level,
              'gender' => $gender_value
            ]);
          }
        }
      }
    }
  }
}

// Get existing quality assessment data
$qualityQuery = "SELECT * FROM quality_assessment WHERE quarter = $selectedQuarter AND year = $year";
$qualityResult = $conn->query($qualityQuery);
$qualityData = [];
while($row = $qualityResult->fetch_assoc()) {
  $keyId = ($row['gender'] == 1 ? 'male' : 'female').'-'.$row['type'].'-'.$row['grade_level'].'-'.$row['school_id'];
  $qualityData[$keyId] = $row['count'];
}
$qualityKeys = array_keys($qualityData);

// At the top of your file, after getting the selected quarter
$selectedQuarter = isset($_POST['quarter']) ? $_POST['quarter'] : $currentQuarter;
$year = date('Y');

// Get existing quality assessment data
function getExistingData($conn, $quarter, $year) {
  $query = "SELECT quality_assessment.*, users.first_name, users.last_name, schools.name as school_name 
            FROM quality_assessment 
            INNER JOIN users ON users.id = quality_assessment.last_user_save
            LEFT JOIN schools ON schools.id = users.school_id 
            WHERE quarter = ? AND year = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $quarter, $year);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $data = [];
  $lastUserSave = "No edits yet"; // Default value
  while ($row = $result->fetch_assoc()) {
    $gender = ($row['gender'] == 1) ? 'male' : 'female';
    $keyId = $gender.'-'.$row['type'].'-'.$row['grade_level'].'-'.$row['school_id'];
    $data[$keyId] = $row['count'];
    // Update last editor info
    $lastUserSave = $row['last_name'].', '.$row['first_name'].' ('.($row['school_name'] ?? '').')';
  }
  
  return ['data' => $data, 'lastUserSave' => $lastUserSave];
}

// Call the function and extract both the data and lastUserSave
$existingDataResult = getExistingData($conn, $selectedQuarter, $year);
$qualityData = $existingDataResult['data'];
$lastUserSave = $existingDataResult['lastUserSave'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quality Assessment Data Entry</title>
  <!-- Include your CSS and other head elements -->
  <style>
    /* Add to your existing styles */
    .table input.form-control-sm {
      width: 80px;
      margin: 0 auto;
      text-align: center;
    }
    
    .table th {
      text-align: center;
      vertical-align: middle;
      background-color: #f8f9fa;
    }
    
    .table td {
      vertical-align: middle;
    }
    
    .card-header h4 {
      margin-bottom: 0;
    }
    
    .font-weight-bold td {
      background-color: #f8f9fa;
    }
  </style>
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include_once "sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include_once "navbar.php"; ?>
        
        <div class="container-fluid">
          <h1 class="h3 mb-2 text-gray-800">Quality Assessment Data Entry</h1>
          <p class="mb-4">Enter quality assessment data for analysis and comparison.</p>

          <form action="qualityAdd.php" method="post">
            <!-- Add this hidden input field inside your form -->
            <input type="hidden" name="activeTab" id="activeTab" value="">
            <input type="hidden" name="activeGradeLevel" id="activeGradeLevel" value="">

            <!-- Quarter selection and buttons -->
            <div class="row mb-4">
              <div class="col-md-6">
                <label for="quarter">Select Quarter:</label>
                <select id="quarter" name="quarter" class="form-control d-inline-block w-auto mr-2">
                  <?php for($i = 1; $i <= 4; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($selectedQuarter == $i) ? 'selected' : ''; ?>><?php echo $i; ?><?php 
                      if($i == 1) echo 'st';
                      else if($i == 2) echo 'nd'; 
                      else if($i == 3) echo 'rd';
                      else echo 'th';
                    ?> Quarter</option>
                  <?php endfor; ?>
                </select>
                <select id="year" name="year" class="form-control d-inline-block w-auto mr-2">
                  <?php for($i = $year; $i <= $year + 1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-md-6 text-right">
                Last Edited By: <?php echo $lastUserSave; ?>
                <button type="submit" class="btn btn-primary" name="save">Save Changes</button>
              </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="als-tab" data-toggle="tab" href="#als" role="tab" onclick="activeTab('als-1')">ALS</a>
              </li>
              <!-- English Reading tabs -->
              <li class="nav-item">
                <a class="nav-link" id="eng-frustration-tab" data-toggle="tab" href="#eng-frustration" role="tab">English Reading Frustration</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="eng-instructional-tab" data-toggle="tab" href="#eng-instructional" role="tab">English Reading Instructional</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="eng-independent-tab" data-toggle="tab" href="#eng-independent" role="tab">English Reading Independent</a>
              </li>
              <!-- Filipino Reading tabs -->
              <li class="nav-item">
                <a class="nav-link" id="fil-frustration-tab" data-toggle="tab" href="#fil-frustration" role="tab">Filipino Reading Frustration</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="fil-instructional-tab" data-toggle="tab" href="#fil-instructional" role="tab">Filipino Reading Instructional</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="fil-independent-tab" data-toggle="tab" href="#fil-independent" role="tab">Filipino Reading Independent</a>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
              <!-- als Tab Content -->
              <div class="tab-pane fade show active" id="als" role="tabpanel">
                <div class="row">
                  <div class="col-md-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Total Number of Learning Sessions Conducted (ALS)</h4>
                      </div>
                      <div class="card-body">
                        <table class="table table-bordered">
                          <thead>
                            <tr>
                              <th rowspan="2">No.</th>
                              <th rowspan="2">NAME OF SCHOOL</th>
                              <th colspan="1" class="text-center">BLP</th>
                              <th colspan="1" class="text-center">A & E – ELEMENTARY</th>
                              <th colspan="1" class="text-center">A & E – JHS</th>
                            </tr>
                            <tr>
                              <th class="text-center">TOTAL</th>
                              <th class="text-center">TOTAL</th>
                              <th class="text-center">TOTAL</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $schoolResult->data_seek(0);
                            $counter = 1;
                            while($row = $schoolResult->fetch_assoc()) {
                              echo "<tr>";
                              echo "<td>".$counter."</td>";
                              echo "<td>".$row['name']."</td>";
                              echo "<td>
                                <input type='number' 
                                       class='form-control form-control-sm' 
                                       name='als-blp[".$row['id']."]' 
                                       value='".(isset($existingData['als-blp']) && isset($existingData['als-blp'][$row['id']]) ? $existingData['als-blp'][$row['id']] : '')."'>
                              </td>";
                              echo "<td>
                                <input type='number' 
                                       class='form-control form-control-sm' 
                                       name='als-elementary[".$row['id']."]' 
                                       value='".(isset($existingData['als-elementary']) && isset($existingData['als-elementary'][$row['id']]) ? $existingData['als-elementary'][$row['id']] : '')."'>
                              </td>";
                              echo "<td>
                                <input type='number' 
                                       class='form-control form-control-sm' 
                                       name='als-jhs[".$row['id']."]' 
                                       value='".(isset($existingData['als-jhs']) && isset($existingData['als-jhs'][$row['id']]) ? $existingData['als-jhs'][$row['id']] : '')."'>
                              </td>";
                              echo "</tr>";
                              $counter++;
                            }
                            ?>
                            <tr class="font-weight-bold">
                              <td colspan="2" class="text-right">TOTAL</td>
                              <td class="blp-total">0</td>
                              <td class="elementary-total">0</td>
                              <td class="jhs-total">0</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- English Reading Tabs Content -->
              <?php
              $readingLevels = ['frustration', 'instructional', 'independent'];
              foreach($readingLevels as $level) {
                echo "<div class='tab-pane fade' id='eng-{$level}' role='tabpanel'>";
                echo "<div class='row'>";
                echo "<div class='col-md-3'>";
                echo "<div class='nav flex-column nav-pills' role='tablist'>";
                echo str_replace('dynamicId', "eng-{$level}", $grade_levels);
                echo "</div>";
                echo "</div>";
                echo "<div class='col-md-9'>";
                echo "<div class='tab-content'>";
                echo "<table class='table'>";
                echo "<thead><tr><th>School Name</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>";
                echo "<tbody>";
                
                $schoolResult->data_seek(0);
                while($row = $schoolResult->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>".$row['name']."</td>";
                  echo "<td>
                    <input type='number' 
                           class='form-control form-control-sm' 
                           name='eng-{$level}-male[".$row['id']."]' 
                           value='".(isset($existingData['eng-'.$level.'-male'][$row['id']]) ? $existingData['eng-'.$level.'-male'][$row['id']] : '')."'>
                  </td>";
                  echo "<td>
                    <input type='number' 
                           class='form-control form-control-sm' 
                           name='eng-{$level}-female[".$row['id']."]' 
                           value='".(isset($existingData['eng-'.$level.'-female'][$row['id']]) ? $existingData['eng-'.$level.'-female'][$row['id']] : '')."'>
                  </td>";
                  echo "<td class='total'>0</td>";
                  echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
              }
              ?>

              <!-- Filipino Reading Tabs Content -->
              <?php
              foreach($readingLevels as $level) {
                echo "<div class='tab-pane fade' id='fil-{$level}' role='tabpanel'>";
                echo "<div class='row'>";
                echo "<div class='col-md-3'>";
                echo "<div class='nav flex-column nav-pills' role='tablist'>";
                echo str_replace('dynamicId', "fil-{$level}", $grade_levels);
                echo "</div>";
                echo "</div>";
                echo "<div class='col-md-9'>";
                echo "<div class='tab-content'>";
                echo "<table class='table'>";
                echo "<thead><tr><th>School Name</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>";
                echo "<tbody>";
                
                $schoolResult->data_seek(0);
                while($row = $schoolResult->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>".$row['name']."</td>";
                  echo "<td>
                    <input type='number' 
                           class='form-control form-control-sm' 
                           name='fil-{$level}-male[".$row['id']."]' 
                           value='".(isset($existingData['fil-'.$level.'-male'][$row['id']]) ? $existingData['fil-'.$level.'-male'][$row['id']] : '')."'>
                  </td>";
                  echo "<td>
                    <input type='number' 
                           class='form-control form-control-sm' 
                           name='fil-{$level}-female[".$row['id']."]' 
                           value='".(isset($existingData['fil-'.$level.'-female'][$row['id']]) ? $existingData['fil-'.$level.'-female'][$row['id']] : '')."'>
                  </td>";
                  echo "<td class='total'>0</td>";
                  echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
              }
              ?>
            </div>
          </form>
        </div>
      </div>
      <?php include_once "footer.php"; ?>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    function activeTab(tab) {
      $('#activeTab').val(tab);
      lockFields();
    }

    $(document).ready(function() {
      // Set initial active tab value when page loads
      $('#activeTab').val('als-1'); // Or whatever your default tab should be

      // Update active tab value when tabs are clicked
      $('.nav-link').on('click', function() {
        var tabId = $(this).attr('href').substring(1); // Remove the # from href
        var gradeLevel = $(this).data('grade-level') || '1';
        $('#activeTab').val(tabId + '-' + gradeLevel);
        $('#activeGradeLevel').val(gradeLevel);
      });

      // Initialize Bootstrap tabs
      $('#myTab a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        lockFields();
      });

      // Update totals when input changes
      $('input[type="number"]').on('change', function() {
        updateTotal($(this).closest('tr'));
      });

      // Load existing data
      var qualityData = <?php echo json_encode($qualityData); ?>;
      var qualityKeys = <?php echo json_encode($qualityKeys); ?>;
      
      // Function to update totals
      function updateTotal(row) {
        var male = parseInt(row.find('input[name*="male"]').val()) || 0;
        var female = parseInt(row.find('input[name*="female"]').val()) || 0;
        row.find('.total').text(male + female);
      }

      // Initialize with existing data
      for(var key in qualityData) {
        var input = $('input[name="' + key + '"]');
        if(input.length) {
          input.val(qualityData[key]);
          updateTotal(input.closest('tr'));
        }
      }

      // Add this to your existing script section
      function updateALSTotals() {
        let blpTotal = 0;
        let elementaryTotal = 0;
        let jhsTotal = 0;

        // Calculate BLP total
        $('input[name^="als-blp"]').each(function() {
          blpTotal += parseInt($(this).val()) || 0;
        });

        // Calculate Elementary total
        $('input[name^="als-elementary"]').each(function() {
          elementaryTotal += parseInt($(this).val()) || 0;
        });

        // Calculate JHS total
        $('input[name^="als-jhs"]').each(function() {
          jhsTotal += parseInt($(this).val()) || 0;
        });

        // Update totals in the table
        $('.blp-total').text(blpTotal);
        $('.elementary-total').text(elementaryTotal);
        $('.jhs-total').text(jhsTotal);
      }

      // Add this to your document ready function
      $('input[name^="als"]').on('input', function() {
        updateALSTotals();
      });

      // Initial calculation
      updateALSTotals();

      // Populate saved data
      var qualityData = <?php echo json_encode($qualityData); ?>;
      var role = <?php echo $_SESSION['role']; ?>; // Assuming you have role

      // Update totals on page load
      updateAllTotals();
      
      // Update totals when input changes
      $('input[type="number"]').on('change', function() {
        updateAllTotals();
      });
      
      // Function to update row totals
      function updateRowTotal(row) {
        var total = 0;
        row.find('input[type="number"]').each(function() {
          total += parseInt($(this).val()) || 0;
        });
        row.find('.total').text(total);
      }
      
      // Function to update all totals
      function updateAllTotals() {
        // Update ALS totals
        var blpTotal = 0, elementaryTotal = 0, jhsTotal = 0;
        
        $('input[name^="als-blp"]').each(function() {
          blpTotal += parseInt($(this).val()) || 0;
        });
        $('.blp-total').text(blpTotal);
        
        $('input[name^="als-elementary"]').each(function() {
          elementaryTotal += parseInt($(this).val()) || 0;
        });
        $('.elementary-total').text(elementaryTotal);
        
        $('input[name^="als-jhs"]').each(function() {
          jhsTotal += parseInt($(this).val()) || 0;
        });
        $('.jhs-total').text(jhsTotal);
        
        // Update Reading totals
        $('.table tr').each(function() {
          if($(this).find('input[type="number"]').length) {
            updateRowTotal($(this));
          }
        });
      }
      
      // Handle tab changes
      $('.nav-link').on('click', function() {
        var tabId = $(this).attr('href');
        $(tabId).find('tr').each(function() {
          if($(this).find('input[type="number"]').length) {
            updateRowTotal($(this));
          }
        });
      });
      
      // Handle quarter changes
      $('#quarter').on('change', function() {
        $(this).closest('form').submit();
      });
    });
  </script>
</body>
</html>
