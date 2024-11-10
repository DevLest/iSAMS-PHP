// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Pie Chart Example
var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["ALS", "Elementary", "JHS/SHS"],
        datasets: [{
            data: [
                pieChartData.als,
                pieChartData.elementary,
                pieChartData.secondary
            ],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
            callbacks: {
                label: function(tooltipItem, data) {
                    var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                    var value = data.datasets[0].data[tooltipItem.index];
                    var percentage = Math.round((value / total) * 100);
                    return data.labels[tooltipItem.index] + ': ' + value + ' (' + percentage + '%)';
                }
            }
        },
        legend: {
            display: true,
            position: 'bottom'
        },
        cutoutPercentage: 80,
    },
});