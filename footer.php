<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie.js"></script>
<script src="js/demo/chart-bar.js"></script>
<script>
    function checkNotifications() {
        $.ajax({
            url: 'checkNotifications.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#alertsDropdown .badge-counter').text(data.count > 0 ? data.count : '');
                let dropdown = $('#alertsDropdown').next('.dropdown-list');
                dropdown.empty(); // Clear previous notifications
                dropdown.append('<h6 class="dropdown-header">Alerts Center</h6>');
                if (data.requests && data.requests.length > 0) {
                    data.requests.forEach(function(request) {
                        let displayUsername = request.requester_username === '<?php echo $_SESSION["username"]; ?>' 
                            ? request.processor_username
                            : request.requester_username;
                        
                        // Determine request type text based on status
                        let requestTypeText = request.status === 'pending' 
                            ? 'Edit request' 
                            : `Request ${request.status}`;
                        
                        dropdown.append(`
                            <a class="dropdown-item d-flex align-items-center" href="adminEditRequests.php">
                                <div class="mr-3">
                                    <div class="icon-circle bg-primary">
                                        <i class="fas fa-edit text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500">${request.request_date}</div>
                                    <span>${requestTypeText} from <strong>${displayUsername}</strong> for ${request.type}</span>
                                    <div class="small">${request.grade_level_name}, ${request.gender.charAt(0).toUpperCase() + request.gender.slice(1)}</div>
                                </div>
                            </a>
                        `);
                    });
                } else {
                    dropdown.append(`
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="mr-3">
                                <div class="icon-circle bg-secondary">
                                    <i class="fas fa-bell-slash text-white"></i>
                                </div>
                            </div>
                            <div>
                                <span>No pending requests</span>
                            </div>
                        </a>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
            }
        });
    }

    // Check notifications every 5 seconds
    setInterval(checkNotifications, 5000);
</script>