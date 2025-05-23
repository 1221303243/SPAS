<!DOCTYPE html>
<html>

<head>
    <title>Sales Chart</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', { packages: ['corechart'] });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            let chartData = JSON.parse(localStorage.getItem("chartData")) || [];

            if (chartData.length === 0) {
                document.getElementById('chart_div').innerHTML = "<h3>No data available. Please add data from the input page.</h3>";
                return;
            }

            // Add headers
            chartData.unshift(['Year', 'Sales']);

            let data = google.visualization.arrayToDataTable(chartData);

            let options = { title: 'Company Sales' };

            let chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
</head>

<body>
    <h2>Sales Chart</h2>
    <div id="chart_div" style="width: 600px; height: 400px;"></div>
    <br>
    <a href="input.html">Go to Input Page</a>
</body>

</html>