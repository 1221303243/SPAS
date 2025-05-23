<!DOCTYPE html>
<html>

<head>
    <title>Input Data</title>
</head>

<body>
    <h2>Enter Sales Data</h2>
    <input type="text" id="year" placeholder="Enter Year">
    <input type="number" id="sales" placeholder="Enter Sales">
    <button onclick="saveData()">Save Data</button>
    <br><br>
    <a href="charts.html">Go to Chart</a>

    <script>
        function saveData() {
            let year = document.getElementById("year").value;
            let sales = document.getElementById("sales").value;

            if (year && sales) {
                // Retrieve existing data or initialize
                let chartData = JSON.parse(localStorage.getItem("chartData")) || [];

                // Add new data
                chartData.push([year, parseInt(sales)]);

                // Save to localStorage
                localStorage.setItem("chartData", JSON.stringify(chartData));

                alert("Data saved! Go to the chart page to see the graph.");
            } else {
                alert("Please enter both Year and Sales.");
            }
        }
    </script>
</body>

</html>