<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <img class="sidebar-brand-icon" style="height: 100%;" src="img/logo-img.png" alt="no-image">
        <div class="sidebar-brand-text mx-3">SMEA <sup> Tool</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php if($current_page == 'dashboard.php') echo 'active'; ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Management Modules
    </div>
    
    <li class="nav-item <?php if(in_array($current_page, ["addUser.php", "user-list.php"])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUser" aria-expanded="true"
            aria-controls="collapseUser">
            <i class="fas fa-fw fa-user"></i>
            <span>Users</span>
        </a>
        <div id="collapseUser" class="collapse <?php if(in_array($current_page, ["addUser.php", "user-list.php"])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                    <a class="collapse-item <?php if($current_page == 'addUser.php') echo 'active'; ?>" href="addUser.php">Add</a>
                <?php endif; ?>
                <a class="collapse-item <?php if($current_page == 'user-list.php') echo 'active'; ?>" href="user-list.php">List</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php if(in_array($current_page, ["addSchool.php", "school-list.php", 'school-year-list.php', 'addSchoolYear.php'])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSchool" aria-expanded="true"
            aria-controls="collapseSchool">
            <i class="fas fa-fw fa-school"></i>
            <span>Schools</span>
        </a>
        <div id="collapseSchool" class="collapse <?php if(in_array($current_page, ["addSchool.php", "school-list.php", 'school-year-list.php', 'addSchoolYear.php'])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                    <!-- <a class="collapse-item <?php if($current_page == 'addSchool.php') echo 'active'; ?>" href="addSchool.php">Add</a>
                    <a class="collapse-item <?php if($current_page == 'addSchoolYear.php') echo 'active'; ?>" href="addSchoolYear.php">Add School Year</a> -->
                <?php endif; ?>
                <a class="collapse-item <?php if($current_page == 'school-list.php') echo 'active'; ?>" href="school-list.php">List</a>
                <a class="collapse-item <?php if($current_page == 'school-year-list.php') echo 'active'; ?>" href="school-year-list.php">School Year</a>
            </div>
        </div>
    </li>
    
    <div class="sidebar-heading">
        Monitoring Evaluation Adj
    </div>

    <li class="nav-item <?php if(in_array($current_page, ["attendanceComparative.php", "attendanceAdd.php", 'attendanceConcerns.php'])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAttendance" aria-expanded="true"
            aria-controls="collapseAttendance">
            <i class="fas fa-fw fa-list-alt"></i>
            <span>Access</span>
        </a>
        <div id="collapseAttendance" class="collapse <?php if(in_array($current_page, ["attendanceComparative.php", "attendanceAdd.php", 'attendanceConcerns.php'])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <a class="collapse-item <?php if($current_page == 'attendanceComparative.php') echo 'active'; ?>" href="attendanceComparative.php">Comparative Data</a>
                <a class="collapse-item <?php if($current_page == 'attendanceAdd.php') echo 'active'; ?>" href="attendanceAdd.php">Encode</a>
                <hr class="sidebar-divider">
                <a class="collapse-item <?php if($current_page == 'attendanceConcerns.php') echo 'active'; ?>" href="attendanceConcerns.php">Issues and Concerns</a>
            </div>
        </div>
    </li>
    
    <li class="nav-item <?php if(in_array($current_page, ["qualityComparative.php", "qualityAdd.php", 'qualityConcerns.php'])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseQuality" aria-expanded="true"
            aria-controls="collapseQuality">
            <i class="fas fa-chart-area"></i>
            <span>Quality</span>
        </a>
        <div id="collapseQuality" class="collapse <?php if(in_array($current_page, ["qualityComparative.php", "qualityAdd.php", 'qualityConcerns.php'])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <a class="collapse-item <?php if($current_page == 'qualityComparative.php') echo 'active'; ?>" href="attendanceComparative.php">Comparative Data</a>
                <a class="collapse-item <?php if($current_page == 'qualityAdd.php') echo 'active'; ?>" href="qualityAdd.php">Encode</a>
                <a class="collapse-item <?php if($current_page == 'qualityConcerns.php') echo 'active'; ?>" href="qualityConcerns.php">Issues and Concerns</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php if(in_array($current_page, ["equityComparative.php", "equityAdd.php", 'equityConcerns.php'])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#equity" aria-expanded="true"
            aria-controls="equity">
            <i class="fas fa-fw fa-list-alt"></i>
            <span>Equity</span>
        </a>
        <div id="equity" class="collapse <?php if(in_array($current_page, ["equityComparative.php", "equityAdd.php", 'equityConcerns.php'])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <a class="collapse-item <?php if($current_page == 'equityComparative.php') echo 'active'; ?>" href="equityComparative.php">Comparative Data</a>
                <a class="collapse-item <?php if($current_page == 'equityAdd.php') echo 'active'; ?>" href="equityAdd.php">Encode</a>
                <hr class="sidebar-divider">
                <a class="collapse-item <?php if($current_page == 'equityConcerns.php') echo 'active'; ?>" href="equityConcerns.php">Issues and Concerns</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php if(in_array($current_page, ["rwbDisplacedLearners.php", "rwbBullyingChildAbuse.php", "rwbConcerns.php"])) echo 'active'; ?>">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#rwb" aria-expanded="true"
            aria-controls="rwb">
            <i class="fas fa-fw fa-list-alt"></i>
            <span>RWB</span>
        </a>
        <div id="rwb" class="collapse <?php if(in_array($current_page, ["rwbDisplacedLearners.php", "rwbBullyingChildAbuse.php", "rwbConcerns.php"])) echo 'show'; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-warning py-2 collapse-inner rounded">
                <a class="collapse-item <?php if($current_page == 'rwbDisplacedLearners.php') echo 'active'; ?>" href="rwbDisplacedLearners.php">Displaced Learners</a>
                <a class="collapse-item <?php if($current_page == 'rwbBullyingChildAbuse.php') echo 'active'; ?>" href="rwbBullyingChildAbuse.php">Bullying & Child Abuse</a>
                <hr class="sidebar-divider">
                <a class="collapse-item <?php if($current_page == 'rwbConcerns.php') echo 'active'; ?>" href="rwbConcerns.php">Issues and Concerns</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Pages Collapse Menu -->
    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true"
            aria-controls="collapseTwo">
            <i class="fas fa-fw fa-cog"></i>
            <span>Components</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Custom Components:</h6>
                <a class="collapse-item" href="buttons.html">Buttons</a>
                <a class="collapse-item" href="cards.html">Cards</a>
            </div>
        </div>
    </li> -->

    <!-- Nav Item - Utilities Collapse Menu -->
    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
            aria-expanded="true" aria-controls="collapseUtilities">
            <i class="fas fa-fw fa-wrench"></i>
            <span>Utilities</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Custom Utilities:</h6>
                <a class="collapse-item" href="utilities-color.html">Colors</a>
                <a class="collapse-item" href="utilities-border.html">Borders</a>
                <a class="collapse-item" href="utilities-animation.html">Animations</a>
                <a class="collapse-item" href="utilities-other.html">Other</a>
            </div>
        </div>
    </li> -->

    <!-- Divider -->
    <!-- <hr class="sidebar-divider"> -->

    <!-- Heading -->
    <!-- <div class="sidebar-heading">
        Addons
    </div> -->

    <!-- Nav Item - Pages Collapse Menu -->
    <!-- <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true"
            aria-controls="collapsePages">
            <i class="fas fa-fw fa-folder"></i>
            <span>Pages</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded"> 
                <h6 class="collapse-header">Login Screens:</h6>
                <a class="collapse-item" href="login.html">Login</a>
                <a class="collapse-item" href="register.html">Register</a>
                <a class="collapse-item" href="forgot-password.html">Forgot Password</a>
                <div class="collapse-divider"></div>
                <h6 class="collapse-header">Other Pages:</h6>
                <a class="collapse-item" href="404.html">404 Page</a>
                <a class="collapse-item" href="blank.html">Blank Page</a>
            </div>
        </div>
    </li> -->

    <!-- Nav Item - Charts -->
    <!-- <li class="nav-item">
        <a class="nav-link" href="charts.html">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Charts</span></a>
    </li> -->

    <!-- Nav Item - Tables -->
    <!-- <li class="nav-item">
        <a class="nav-link" href="tables.html">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span></a>
    </li> -->

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>