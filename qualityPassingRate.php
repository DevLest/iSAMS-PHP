<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection/db.php";
include_once('header.php');

$sql = "SELECT * FROM schools";

$schools = "";
$schools = $conn->query($sql);

$inputTables = "";
if ($schools->num_rows > 0) {
    while($row = $schools->fetch_assoc()) {
        $inputTables .= "
            <tr>
                <td>".$row["name"]."</td>
                <td><input type='number' class='form-control form-control-sm' name='male[]' value='0'></td>
                <td><input type='number' class='form-control form-control-sm' name='female[]' value='0'></td>
                <td class='total'>0</td>
            </tr>
        ";
    }
}

$currentMonth = date('n');
$currentQuarter = ceil($currentMonth / 3);

$sql = "SELECT * FROM grade_level";
$grade_level = $conn->query($sql);

$grade_levels = "";
if ($grade_level->num_rows > 0) {
    $count = 0;
    while($row = $grade_level->fetch_assoc()) {
        $grade_level_inputs = "";
        $active = $count == 0 ? "true" : "false";
        $rowid = $row['id'];

        $grade_levels .= "<a class='nav-link ".($count == 0 ? 'active' : '')."' id='v-pills-dynamicId-$rowid-tab' onclick=\"activeTab('dynamicId-$rowid')\" data-toggle='pill' href='#v-pills-dynamicId-$rowid' role='tab' aria-controls='v-pills-dynamicId-$rowid' aria-selected='$active'>".$row['name']."</a>";
        
        $grade_level_inputs .= "<div class='tab-pane fade show active' id='v-pills-dynamicId-$rowid' role='tabpanel' aria-labelledby='v-pills-dynamicId-$rowid-tab'>
                                    <table class='table'>
                                    <thead>
                                        <tr>
                                            <th scope='col'>Name</th>
                                            <th scope='col'>Male</th>
                                            <th scope='col'>Female</th>
                                            <th scope='col'>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ".$inputTables."
                                    </tbody>
                                </table>
                                </div>";

        $count++;
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
                    </style>

                    <div class="container-fluid">

                    <h1 class="h3 mb-2 text-gray-800">Passing Rate</h1>
                    <p class="mb-4">Percentage of Passing by Subject Area in Quarterly Academic Assessment</p>
                    
                    <form action="attendaceAdd.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="quarter">Select Quarter:</label>
                                <select id="quarter" name="quarter">
                                    <option value="1" <?php if ($currentQuarter == 1) echo 'selected'; ?>>1st</option>
                                    <option value="2" <?php if ($currentQuarter == 2) echo 'selected'; ?>>2nd</option>
                                    <option value="3" <?php if ($currentQuarter == 3) echo 'selected'; ?>>3rd</option>
                                    <option value="4" <?php if ($currentQuarter == 4) echo 'selected'; ?>>4th</option>
                                </select>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                        
                        <nav class="navbar navbar-expand-lg navbar-light bg-light navbar-custom">
                            <div class="collapse navbar-collapse" id="navbarNav">
                                <ul class="navbar-nav">
                                    <li class="nav-item-custom active">
                                        <a class="nav-link" href="#" id="tab1">ASL</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab2">PARDOS SARDOS</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab3">Pivate Vourcher</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab4">Tardiness</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab5">Absenteeism</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab6">Severly Wasted</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab7">Wasted</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab8">Normal</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab9">Obese</a>
                                    </li>
                                    <li class="nav-item-custom">
                                        <a class="nav-link" href="#" id="tab10">Overweight</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                        
                        <input type="text" name="activeTab" id="activeTab" value="blp">
                        <div id="tabContent">
                            <div id="tabContent">
                                <div id="content1" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab1" role="tablist" aria-orientation="vertical">
                                                <a class="nav-link active" id="v-pills-blp-tab" onclick="activeTab('tab1-1')" data-toggle="pill" href="#v-pills-blp" role="tab" aria-controls="v-pills-blp" aria-selected="true">BLP</a>
                                                <a class="nav-link" id="v-pills-ae-elem-tab" onclick="activeTab('tab1-2')" data-toggle="pill" href="#v-pills-ae-elem" role="tab" aria-controls="v-pills-ae-elem" aria-selected="false">A & E - Elementary</a>
                                                <a class="nav-link" id="v-pills-ae-jhs-tab" onclick="activeTab('tab1-3')" data-toggle="pill" href="#v-pills-ae-jhs" role="tab" aria-controls="v-pills-ae-jhs" aria-selected="false">A&E - JHS</a>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent1">
                                                <div class="tab-pane fade show active" id="v-pills-blp" role="tabpanel" aria-labelledby="v-pills-blp-tab">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Name</th>
                                                                <th scope="col">Male</th>
                                                                <th scope="col">Female</th>
                                                                <th scope="col">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php echo $inputTables;?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content2" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab2" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab2', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent2">
                                                <?php echo str_replace('dynamicId', 'tab2', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content3" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab3" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab3', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent3">
                                                <?php echo str_replace('dynamicId', 'tab3', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content4" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab4" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab4', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent4">
                                                <?php echo str_replace('dynamicId', 'tab4', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content5" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab5" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab5', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent5">
                                                <?php echo str_replace('dynamicId', 'tab5', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content6" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab6" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab6', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent6">
                                                <?php echo str_replace('dynamicId', 'tab6', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="content7" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab7" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab7', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent7">
                                                <?php echo str_replace('dynamicId', 'tab7', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="content8" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab8" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab8', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent8">
                                                <?php echo str_replace('dynamicId', 'tab8', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="content9" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab9" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab9', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent9">
                                                <?php echo str_replace('dynamicId', 'tab9', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="content10" class="content-tab">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="nav flex-column nav-pills" id="v-pills-tab10" role="tablist" aria-orientation="vertical">
                                                <?php echo str_replace('dynamicId', 'tab10', $grade_levels); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="tab-content" id="v-pills-tabContent10">
                                                <?php echo str_replace('dynamicId', 'tab10', $grade_level_inputs); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <br>

                </div>

            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2021</span>
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
        }

        $(document).ready(function(){
            function updateTotal(row) {
                var male = parseInt(row.find('input')[0].value) || 0;
                var female = parseInt(row.find('input')[1].value) || 0;
                row.find('.total').text(male + female);
            }

            function clearZero(input) {
                if (input.find('input')[0].value == '0') {
                    input.find('input')[0].value = '';
                } else if (input.find('input')[0].value < 1 || input.find('input')[0].value == "") {
                    input.find('input')[0].value = '0';
                }
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

                $("#content" + tabId.substring(3)).show(); 

                $(".nav-item-custom").removeClass("active");
                $(this).parent().addClass("active");

                $('#activeTab').val(tabId+"-1");
            });

            $(".nav-item-custom:first-child a").click();
        });
    </script>

</body>

</html>