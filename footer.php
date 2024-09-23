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
<script src="js/demo/chart-pie-demo.js"></script>
<script src="js/demo/chart-bar-demo.js"></script>
<script>
    function checkNotifications() {
        $.ajax({
            url: 'checkNotifications.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log(data); // Log the response data
                $('#alertsDropdown .badge-counter').text(data.count > 0 ? data.count : '');
                let dropdown = $('#alertsDropdown .dropdown-list');
                dropdown.empty(); // Clear previous notifications
                dropdown.append('<h6 class="dropdown-header">Alerts Center</h6>');
                if (data.requests.length > 0) {
                    data.requests.forEach(function(request) {
                        dropdown.append('<a class="dropdown-item text-center small text-gray-500" href="adminEditRequests.php">Edit request from ' + request.user_name + ' for issue: ' + request.issues + '</a>');
                    });
                } else {
                    dropdown.append('<div class="dropdown-item text-center small text-gray-500">No pending requests</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error); // Log any errors
            }
        });
    }

    // Check notifications every 5 seconds
    setInterval(checkNotifications, 5000);
</script>