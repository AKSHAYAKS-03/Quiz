<?php
include_once 'core_db.php';

session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}
$activeQuizId=$_SESSION['active'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Analytics Dashboard</title>   
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
            margin-left: 265px;
            /* background-color:rgb(4, 0, 20);  */
            background-color:rgba(16, 16, 83, 0.49); 
        }

        .container {
            display: flex;
            width: 90%;
            margin-top: 20px;
        }

        .sidebar {
            position: fixed;
            top: 20px;
            left: 90px;
            height: 100%;
            width: 270px;
            background-color: #13274F; /* Dark blue for sidebar */
            color: #fff; /* White text */
            padding: 20px;
            box-sizing: border-box;
            box-shadow: 2px 2px 5px rgba(152, 146, 146, 0.3);
        }

        .sidebar h3 {
            text-align: center;
        }

        .sidebar form {
            display: flex;
            flex-direction: column;
        }

        .sidebar select, .sidebar button{
            margin: 10px 0;
            padding: 8px;
            border: none;
            border-radius: 5px;
            background-color: #34495e; /* Dark gray background for form elements */
            color: #fff; /* White text */
        }

        .sidebar select:focus, .sidebar button:focus {
            outline: none;
            border: 1px solid #3498db;
        }


        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection {
            background-color: #34495e !important; 
            color: black !important;
            border-radius: 5px;
            padding: 6px;
            border: none;
            font-size: 14px;
        }

        .select2-container--focus .select2-selection {
            border: 1px solid #3498db !important; 
            outline: none !important;
        }
        .select2-dropdown {
            background-color: #34495e !important;
            color: #fff !important;
        }

        .select2-results__option--selected {
            background-color: #13274F !important;
            color: white !important;
        }

        .select2-selection__choice {
            background-color:rgb(201, 218, 225) !important;  /* Dark blue */
            color: white !important;
            border: none !important;
            padding: 6px 8px !important;
            border-radius: 4px !important;
        }

        .select2-selection__choice__display{
            margin-left: 6px;
        }
        /* Adjust text color inside the selected option */
        .select2-selection__choice span {
            color:  #13274F !important;
        }
        
        .main-content {
            width: 95%;
            padding: 10px;
            height: fit-content;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            background-color: #fff; /* White background for content */
        }

        .top-stats, .bottom-stats {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap:10px;
            margin: 10px;
        }

        .stat-box, .stats-container {
            background-color: #f1f1f1; /* Light gray background for stats boxes */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 48%;
        }

        h4 {
            margin-top: 0;
            text-align: center;
            color: #13274F; /* Dark blue text for headings */
        }

        #active-quiz-topper {
            display: flex;
            justify-content: space-around;
        }

        #active-quiz-topper img, #all-time-toppers img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .bar-chart-container {
            display: flex;
            align-items: center; 
            justify-content: center;
            width: 100%;
            height: 200px;
        }

        hr {
            margin: 15px 0;
            border: 1px solid #ddd; /* Light gray border */
        }

        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content, .sidebar {
                width: 100%;
            }

            .top-stats, .bottom-stats  {
                flex-direction: column;
            }

            .stat-box , .stats-container{
                width: 100%;
                margin-bottom: 20px;
            }
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .stats-container .counter-widget{
            background-color: #0a3d62;
            color: white;
            padding: 10px;
            border-radius: 15px;
            text-align: center;
            width: 120px;
            height: 80px;
            box-shadow: 3px 3px 15px rgba(0, 0, 0, 0.2);
            font-family: Arial, sans-serif;
            position: relative;
        }
        .stats-container .counter-widget:hover {
            transform: scale(1.02);
            transition: transform 0.3s ease-in-out;
        }
        .counter-widget .icon {
            font-size: 22px;
            margin-bottom: 4px;
        }
        .counter-widget h4 {
            font-size: 14px;
            margin: 3px 0;
        }
        .counter {
            font-size: 16px;
            font-weight: bold;
        }   
        
        #all-year-toppers {
            width: 350px; 
            background: #f1f1f1;
            border-radius: 8px;
            padding: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        #all-year-toppers h4 {
            font-size: 14px;
            color: #333;
            margin-bottom: 6px;
        }

        #all-time-toppers {
            display: flex;
            flex-direction: column; /* Ensure items are listed one by one */
            background-color: #f1f1f1;
        }

        #all-time-toppers > div {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 7px;
            border-radius: 8px;
            margin: 6px 0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        #all-time-toppers h5 {
            font-size: 14px;
            color: #13274F;
            width: 22%;
            text-align: left;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .topper-container {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 6px;
        }

        .topper-container img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .topper-details {
            display: flex;
            flex-direction: column;
            flex: 1; /* Ensures equal space between name/percentage and reg no */
        }

        .topper-details p {
            margin: 0;
            font-size: 13px;
            color: #333;
            text-align: left;
            line-height: 1.3;
        }

        .topper-details p strong {
            color: #555;
        }

        .bar-chart-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .bar-chart-container {
            width: 100%;
        }

        .nav-btn {
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            color: black;
            font-size: 20px;
        }

        .nav-btn:hover {
            scale: 1.1;
            color:rgb(40, 76, 101);
        }


        /* Responsive Design */
@media screen and (max-width: 1024px) {
    .container {
        flex-direction: column;
        width: 95%;
    }

    .sidebar {
        width: 100%;
        text-align: center;
    }

    .main-content {
        width: 100%;
    }

    .top-stats, .bottom-stats {
        flex-direction: column;
    }

    .stat-box, .stats-container {
        width: 100%;
        margin-bottom: 20px;
    }

    .stats-container {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
}

@media screen and (max-width: 1600px) {
    .container {
        min-height: 95vh;
        width: 85%;
        max-width: 1800px;
    }
    .sidebar {
        width: 17.5%;
        font-size: 16px;
    }
    .main-content {
        width: 100%;
    }

    .counter-widget {
        width: 100px;
        height: 75px;
        font-size: 16px;
    }
}

@media screen and (min-width: 1920px) { /* Large screens like 21.5-inch PC */
    .container {
        min-height: 95vh;
        width: 72%;
        max-width: 1800px;
    }

    .sidebar {
        width: 20%;
        min-width: 280px;
        font-size: 18px;
    }

    .main-content {
        width: 80%;
    }

    .stat-box, .stats-container {
        padding: 25px;
        font-size: 20px;
    }

    .counter-widget {
        width: 160px;
        height: 100px;
        font-size: 18px;
    }
}
.header {
    position: fixed;
    top: 10px; 
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header img {
    width: 26px;
    height: 26px;
}
#logout {
    margin-right: 10px;
}
#back{
    margin-left: 10px;
}
    </style>
</head>
    <div class="header">
            <a href="admin.php" id="back" title="Back">
                <img src="icons\back_white.svg" alt="back">
            </a>
            <a href="logout.php" id="logout" title="Log Out">
                <img src="icons\exit_white.svg" alt="exit">
            </a>
    </div>
<body>    
    <div class="container">
        <div class="sidebar">
            <h3>Filter Options</h3>
            <form method="GET" action="">
                <label for="quiz">Quiz:</label>
                <select id="quiz" multiple onchange="fetchQuizDetails()">
                    <option value="all" selected>All</option>
                    <?php
                    $sql = "SELECT Quiz_Id, QuizName FROM quiz_details";
                    $options = $conn->query($sql);
                    if ($options->num_rows > 0) {
                        while ($row = $options->fetch_assoc()) {
                            $id = $row['Quiz_Id'];
                            $name = $row['QuizName'];
                            echo "<option value='$id'>" . $name . "</option>";
                        }
                    }
                    ?>
                </select><br/>
                <input type="hidden" value="all" id="selectedQuizId" name="quizId">

                <label for="year">Year:</label>
                <select id="year" onchange="fetchQuizDetails()">
                    <option value="all" selected>All Years</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                </select><br>

                <label for="section">Section:</label>
                <select id="section" onchange="fetchQuizDetails()">
                    <option value="all" selected>All Sections</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select><br>

                <label for="dept">Department:</label>
                <select id="department" onchange="fetchQuizDetails()">
                    <option value="all" selected>All Departments</option>
                    <option value="CSE">CSE</option>
                    <option value="IT">IT</option>
                    <option value="ECE">ECE</option>
                    <option value="EEE">EEE</option>
                    <option value="MECH">MECH</option>
                    <option value="CIV">CIV</option>
                </select><br>
                
            </form>
        </div>

        <div class="main-content">
            <div class="bottom-stats">
                <!-- Real-Time Performance Chart -->
                <div class="stat-box" style="height: 350px; width: 650px;">
                    <h4>Real-Time Performance</h4>
                    <div class="bar-chart-container" style="height: 350px; width: 650px;">
                        <canvas id="realTimeChart"></canvas>
                    </div>
                </div>

                <div class="stats-container">
                    <div class="counter-widget" style="background-color: rgba(77, 81, 91, 0.55);">
                        <div class="icon">🏆</div>
                        <h4 id="c1">Total Quizzes</h4>
                        <div class="counter" id="c1_val"></div>
                    </div>
                    <div class="counter-widget" style="background-color: rgba(22, 77, 79, 0.63);">
                        <div class="icon">👨‍🎓</div>
                        <h4 id="c2">Total Students</h4>
                        <div class="counter" id="c2_val"></div>
                    </div>
                    <div class="counter-widget" style="background-color:rgba(118, 131, 173, 0.72);">
                        <div class="icon">📊</div>
                        <h4 id="c3">Average Performance</h4>
                        <div class="counter" id="c3_val"></div>
                    </div>
                    <div class="counter-widget" style="background-color:rgba(108, 145, 210, 0.5);">
                        <div class="icon">🌟</div>
                        <h4 id="c4">Best Score Rate</h4>
                        <div class="counter" id="c4_val"></div>
                    </div>
                </div>   
            </div>

            <div class="top-stats">                
                
                <div id="all-year-toppers">
                    <h4>Top Performers (Year-wise)</h4>
                    <div id="all-time-toppers">
                    </div>
                </div>

                <div class="stat-box" style="width: 280px; height:300px; margin-bottom: 0px; padding-bottom:0px">
                    <h4 style="margin: 0px;">Average Percentage</h4>
                    <div class="bar-chart-container" style="width: 280px; height: 280px; ">
                        <canvas id="pieChart" style="width: 100%; height: 100%; padding:0px; margin:0px"></canvas>
                    </div>
                </div>

                <div class="stat-box"  style=" height: 420px; margin-top:-140px; width:290px ">
                    <h4>Toppers</h4>
                    <div class="bar-chart-container"  style="width: 100%; height: 400px;">
                        <canvas id="activeQuizTopperChart"  style="width: 100%; height: 370px;"></canvas>
                    </div>
                </div>
        </div>
        <div class="bottom-stats">
            <div class="stat-box" id="completionTimeDistribution">
                <h4>Completion Time Distribution</h4>
                <div class="bar-chart-container">
                    <canvas id="completionTimeDistributionChart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="stat-box" style="padding: 14px;">
                <h4>Performance Comparison</h4>
                <div class="bar-chart-wrapper">
                    <button id="prev" class="nav-btn">⬅</button>
                    <div class="bar-chart-container" style="height: 165px;">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                    <button id="next" class="nav-btn">➡</button>
                </div>
                <p id="currentView">Viewing: Year-wise Performance</p>
            </div>

        </div>
        
        <div class="bottom-stats">
            <div class="stat-box" id="performance" style="width: 960px; height:100%">
                <h2> Average Performance (avg vs count)</h2>
                <div class="bar-chart-container" style="width:100%; height:100%">
                    <canvas id="performanceBarChart" ></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            fetchQuizDetails();
        });

        $(document).ready(function() {
            $('#quiz').select2();
            $('#quiz').on('change', fetchQuizDetails);
        });

        function animateCounter(target, value, extra = ''){
            console.log(target);
            let current = 0;
            value = parseFloat(value);
            const step = value / 10; 
            let fixed = 0;
            if(target.id==='c4_val')
                fixed = 2;
            const interval = setInterval(function() {
                current += step;
                if (current >= value) {
                    current = value;
                    clearInterval(interval);
                }
                target.innerText = extra+" "+current.toFixed(fixed); 
            }, 10);
        }


        // Bar chart for Active Quiz Topper
        function renderToppersChart(toppers) {
            const ctx = document.getElementById('activeQuizTopperChart').getContext('2d');

            if (toppers.length >= 3) {
                toppers = [toppers[1], toppers[0], toppers[2]];
            }
            
            const labels = toppers.map(topper => topper.name);  
            const percentages = toppers.map(topper => parseFloat(topper.percentage));

            const avatars = toppers.map(topper => topper.avatar ? topper.avatar : 'uploads/65/pokemon_bg.png');
            const topperDetails = toppers.map(topper => ({
                RegNo: topper.RegNo,
                year: topper.year,
                section: topper.section,
                department: topper.department,
                avatar: topper.avatar
            }));

            if (window.activeQuizTopperChart && typeof window.activeQuizTopperChart.destroy === 'function') {
                window.activeQuizTopperChart.destroy();
            }

            const avatarPlugin = {
                id: 'customAvatarPlugin',
                beforeDatasetsDraw(chart, args, options) {
                    const ctx = chart.ctx;
                    const bars = chart.getDatasetMeta(0).data;
                    const tooltip = chart.tooltip;

                    bars.forEach((bar, index) => {
                        if (!topperDetails[index].avatar) return;

                        const avatarSize = 40;
                        let x = bar.x - avatarSize / 2;
                        let y = bar.y - avatarSize - 10; // Position above bar

                        const img = new Image();
                        img.src = topperDetails[index].avatar;
                        img.onload = function () {
                            ctx.save();
                        
                            if (tooltip && tooltip.opacity > 0 && tooltip.dataPoints) {
                                const tooltipIndex = tooltip.dataPoints[0].dataIndex;
                                if (tooltipIndex === index) {
                                    ctx.globalAlpha = 0.3; // Reduce opacity when tooltip is active
                                } else {
                                    ctx.globalAlpha = 1; // Normal opacity
                                }
                            } else {
                                ctx.globalAlpha = 1; // Default opacity
                            }

                            ctx.drawImage(img, x, y, avatarSize, avatarSize);
                            ctx.restore();
                        };
                    });
                }
            };


            const maxPercentage = Math.max(...percentages);
            const yAxisMax = maxPercentage + 20; 

            // Create new bar chart
            window.activeQuizTopperChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels, 
                    datasets: [{
                        label: 'Average Score',
                        data: percentages, 
                        backgroundColor: ['#13274F', '#34495e', '#95a5a6'], // Dark blue, dark gray, light gray
                        borderColor: ['#13274F', '#34495e', '#95a5a6'],
                        borderWidth: 0.8
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: yAxisMax, 
                            ticks: {
                                stepSize: 10
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    const index = tooltipItem.dataIndex;
                                    const topper = topperDetails[index];

                                    return [
                                        `Reg No: ${topper.RegNo}`,
                                        `Year: ${topper.year}`,
                                        `Section: ${topper.section}`,
                                        `Department: ${topper.department}`
                                    ];
                                }
                            }
                        }
                    }
                },
                plugins: [avatarPlugin] 
            });
        }

        function renderCompletionTimeDistribution(completionTimeData) {
            const canvas = document.getElementById("completionTimeDistributionChart");

            if (window.completionTimeDistributionChart && typeof window.completionTimeDistributionChart.destroy === "function") {
                window.completionTimeDistributionChart.destroy();
            }

            const ctx = canvas.getContext("2d");

            var interval =10;
            if(completionTimeData.length>1) {
                interval = completionTimeData[1].timeRange - completionTimeData[0].timeRange;         
            }

            const labels = completionTimeData.map(item => {
                const nextRange = item.timeRange + interval; 
                return `${item.timeRange}-${nextRange} sec`;
            });

            const counts = completionTimeData.map(item => {
                return parseInt(item.studentCount, 10); // Convert studentCount to number
            });

            console.log("labels" + labels);    
            console.log("counts"+counts);
            window.completionTimeDistributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Students Count',
                        data: counts,
                        backgroundColor: 'rgba(104, 84, 117, 0.48)',
                        borderColor: 'rgb(21, 21, 22)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }


        function renderPerformanceBarChart(avgPerformance) {
            const avgPerformanceElement = document.getElementById('performanceBarChart');
            
            const ctx = avgPerformanceElement.getContext('2d');
            ctx.clearRect(0, 0, avgPerformanceElement.width, avgPerformanceElement.height);

            if (window.performanceBarChart && typeof window.performanceBarChart.destroy === "function") {
                console.log('destroyed');
                window.performanceBarChart.destroy();
            }
            const labels = Object.keys(avgPerformance);
            const values = Object.values(avgPerformance);

            window.performanceBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'No.of Students',
                        data: values,
                        backgroundColor: 'rgba(72, 126, 201, 0.55)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

            function fetchQuizDetails() {
                let selectedQuizzes = $('#quiz').val();
                $('#selectedQuizId').val(selectedQuizzes.join(',')); 
                console.log("selectedQuizzes: " + selectedQuizzes);
                
                const quiz = document.getElementById('selectedQuizId').value;
                const year = document.getElementById('year').value;
                const section = document.getElementById('section').value;
                const department = document.getElementById('department').value;
                console.log("quiz: "+quiz+", year: "+year+", section: "+section+", department: "+department);

                fetch('fetchQuizDetails.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `quiz=${quiz}&year=${year}&section=${section}&department=${department}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Fetched Data: ", data); // Debugging output

                    // Update statistics dynamically
                    document.getElementById('c1').innerText = data.c1;
                    document.getElementById('c2').innerText = data.c2;
                    document.getElementById('c3').innerText = data.c3;
                    document.getElementById('c4').innerText = data.c4;

                    c1 = document.getElementById('c1_val');
                    animateCounter(c1, data.c1_val);

                    c2 = document.getElementById('c2_val');
                    animateCounter(c2, data.c2_val);

                    const c4 = document.getElementById('c4_val');
                    if (typeof data.c4_val === 'object' && data.c4_val.bestQuizName) {
                        animateCounter(c4, data.c4_val.bestQuizRate, data.c4_val.bestQuizName); 
                    } else {
                        animateCounter(c4, data.c4_val); 
                    }
                    
                    c3 = document.getElementById('c3_val');
                    c3.innerText = '0%';
                    let currentPercentage = 0;
                    console.log("avg percentage: "+data.c3_val);
                    const passStep = data.c3_val / 10;
                    const passInterval = setInterval(function() {
                        currentPercentage += passStep;
                        if (currentPercentage >= data.c3_val) {
                            currentPercentage = data.c3_val;
                            clearInterval(passInterval);
                        }
                        c3.innerText = currentPercentage+ '%';
                    }, 30);

                    
                    // Update charts
                    renderToppersChart(data.topToppers);

                    // update completion time chart 
                    console.log(data.completionTimeData); 
                    renderCompletionTimeDistribution(data.completionTimeData);

                    renderPerformanceBarChart(data.avgPerformance);

                    // piechart
                    const canvas = document.getElementById('pieChart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    const labels = Object.keys(data.percentages);
                    const percentages = Object.values(data.percentages);

                    console.log(percentages);
                    console.log(labels);
                    console.log('before destruction');
                    if (window.pieChart && typeof window.pieChart.destroy === "function") {
                        console.log('destroyed');
                        window.pieChart.destroy();
                    }

                    // Create a new chart instance
                    window.pieChart = new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels:labels,
                            datasets: [{
                                label: "Score Distribution", 
                                data: percentages,
                                backgroundColor:["rgba(22, 77, 79, 0.63)", '#13274F', '#34495e', 'black','rgba(108, 145, 210, 0.5)']
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'left', // Move labels to the left side
                                    labels: {
                                        font: {
                                            size: 12 // Increase label size if needed
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // line chart
                    const realTime= document.getElementById('realTimeChart');
                    const realTimeCtx = realTime.getContext('2d');
                    realTimeCtx.clearRect(0, 0, realTimeCtx.width, realTimeCtx.height);
                    console.log('before destruction');
                    if (window.realTimeChart && typeof window.realTimeChart.destroy === "function") {
                        console.log('destroyed');
                        window.realTimeChart.destroy();
                    }

                    window.realTimeChart = new Chart(realTimeCtx, {
                        type: 'line',
                        data: {
                            labels: data.scoreTrend.labels,  
                            datasets: [{
                                label: 'Average Score',
                                data: data.scoreTrend.data,  
                                borderColor: '#34495e',
                                backgroundColor: 'rgba(12, 4, 89, 0.2)',
                                fill: true,
                                tension: 0.4 // for smoothness
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    display: false
                                },
                                y: {
                                    beginAtZero: true
                                }
                            },
                            animation: {
                                duration: 1000, 
                                easing: 'easeInOutQuad'
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem) {
                                            const score = tooltipItem.raw;
                                            if (typeof score === 'number') {
                                                return 'Score: ' + score.toFixed(2);
                                            } else {
                                                const numericScore = parseFloat(score);
                                                return `Score: ${score.toFixed(2)}`;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });


                    // Update toppers section
                    const toppersContainer = document.getElementById('all-time-toppers');
                    toppersContainer.innerHTML = ''; 

                    Object.entries(data.performers).forEach(([year, topper]) => {
                        const yearDiv = document.createElement('div');

                        const yearHeader = document.createElement('h5');
                        yearHeader.textContent = `${year} Year`;
                        yearDiv.appendChild(yearHeader);

                        const topperContainer = document.createElement('div');
                        topperContainer.classList.add('topper-container');

                        const profileImg = document.createElement('img');
                        profileImg.src = topper.avatar ? topper.avatar : 'uploads/65/pokemon_bg.png';
                        console.log(topper.avatar);
                        profileImg.alt = 'Profile Picture';

                        const topperDetails = document.createElement('div');
                        topperDetails.classList.add('topper-details');

                        topperDetails.innerHTML = `
                            <p><strong>${topper.Name}</strong> (${topper.avg_percentage || 'N/A'}%)</p>
                            <p><strong>Reg No:</strong> ${topper.RegNo}</p>
                        `;

                        // Append elements
                        topperContainer.appendChild(profileImg);
                        topperContainer.appendChild(topperDetails);
                        yearDiv.appendChild(topperContainer);

                        // Add to main container
                        toppersContainer.appendChild(yearDiv);
                    });    
                    
                    
                    // comparison chart
                    const comparisonCanvas = document.getElementById('comparisonChart');
                    const comparisonCtx = comparisonCanvas.getContext('2d');
                    const comparisonData = data;  

                    let currentViewIndex = 0;
                    const viewText = document.getElementById('currentView');
                    let isYearlyView = true;  

                    document.getElementById("next").addEventListener("click", function () {
                        const yearKeys = Object.keys(comparisonData.sectionPerformance);
                        const maxIndex = yearKeys.length - 1; 

                        if (isYearlyView) {
                            currentViewIndex = 0;
                            isYearlyView = false;
                        } else {
                            if (currentViewIndex < maxIndex) {
                                currentViewIndex++;
                            } else {
                                isYearlyView = true;
                            }
                        }
                        
                        updateComparisonChart(currentViewIndex, comparisonData, comparisonCtx, viewText, isYearlyView);
                    });

                    // Handle Previous Button Click (Move Backwards)
                    document.getElementById("prev").addEventListener("click", function () {
                        if (!isYearlyView && currentViewIndex > 0) {
                            currentViewIndex--;
                        } else {
                            isYearlyView = true;
                        }
                        
                        updateComparisonChart(currentViewIndex, comparisonData, comparisonCtx, viewText, isYearlyView);
                    });

                    // Function to update the chart
                    function updateComparisonChart(index, data, chart, textElement, isYearlyView) {
                        let chartData = [];
                        let chartLabels = [];
                    
                        if (window.comparisonChart && typeof window.comparisonChart.destroy === "function") {
                            console.log('destroyed comparisonChart');
                            window.comparisonChart.destroy();
                        }

                        if (isYearlyView) {
                            const yearData = data.yearlyPerformance;
                            chartLabels = yearData.map(item => item.year);
                            chartData = yearData.map(item => item.avgPercentage);
                            textElement.textContent = `Viewing: Yearly Performance`;
                        } else {
                            const yearKeys = Object.keys(data.sectionPerformance);
                            const selectedYear = yearKeys[index];
                            const sectionData = data.sectionPerformance[selectedYear]; 

                            if (sectionData) {
                                chartLabels = sectionData.map((item) => item.section);
                                chartData = sectionData.map((item) => item.avgPercentage);
                                textElement.textContent = `Viewing: Section Performance for Year ${selectedYear}`;
                            } else {
                                textElement.textContent = `No section data available`;
                            }
                        }
                        
                        window.comparisonChart = new Chart(comparisonCtx, {
                            type: 'bar',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    label: 'Performance Comparison',
                                    data: chartData,
                                    backgroundColor: 'rgba(22, 77, 79, 0.70)',
                                    barThickness: 12
                                }]
                            },
                            options: {
                                responsive: true,
                                indexAxis: 'y',
                                scales: {
                                    x: {
                                        beginAtZero: true
                                    },
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                    updateComparisonChart(currentViewIndex, comparisonData, comparisonChart, viewText, isYearlyView);

                })
                .catch(error => console.error('Error fetching data:', error));

            }

    </script>
</body>
</html>