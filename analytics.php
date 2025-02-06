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
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
            /* background-color:rgb(4, 0, 20);  */
            background-color:rgba(16, 16, 83, 0.49); 
        }

        .container {
            display: flex;
            width: 90%;
            margin-top: 20px;
        }

        .sidebar {
            width: 20%;
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

        .sidebar select, .sidebar button {
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

        .main-content {
            width: 80%;
            padding: 10px;
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
            padding: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        #all-year-toppers h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
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
            padding: 6px;
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

    .top-stats,
    .bottom-stats {
        flex-direction: column;
    }

    .stat-box,
    .stats-container {
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
        padding: 15px;
    }

    .sidebar select,
    .sidebar button {
        font-size: 14px;
        padding: 8px;
    }

    .counter-widget {
        width: 120px;
        height: 75px;
    }

    .counter-widget h4 {
        font-size: 13px;
    }

    .counter {
        font-size: 15px;
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

    .stat-box,
    .stats-container {
        padding: 25px;
        font-size: 20px;
    }

    .counter-widget {
        width: 160px;
        height: 100px;
        font-size: 18px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h3>Filter Options</h3>
            <form method="GET" action="">
                <label for="quiz">Quiz:</label>
                <select id="quiz" onchange="fetchQuizDetails()">
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
                        <h4>Total Quizzes</h4>
                        <div class="counter" id="totalQuizzes"></div>
                    </div>
                    <div class="counter-widget" style="background-color: rgba(22, 77, 79, 0.63);">
                        <div class="icon">👨‍🎓</div>
                        <h4>Total Students</h4>
                        <div class="counter" id="totalStudents"></div>
                    </div>
                    <div class="counter-widget" style="background-color:rgba(118, 131, 173, 0.72);">
                        <div class="icon">📊</div>
                        <h4>Average Performance</h4>
                        <div class="counter" id="averagePerformance"></div>
                    </div>
                    <div class="counter-widget" style="background-color:rgba(108, 145, 210, 0.5);">
                        <div class="icon">🌟</div>
                        <h4>Best Score Rate</h4>
                        <div class="counter" id="bestQuiz"></div>
                    </div>
                </div>
            
            </div>

            <div class="top-stats">                
                
                <div id="all-year-toppers">
                    <h4>All Time Toppers (Year-wise)</h4>
                    <div id="all-time-toppers">
                    </div>
                </div>

                <div class="stat-box" style="width: 280px;">
                    <h4>Percentage</h4>
                    <div class="bar-chart-container">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>

                <div class="stat-box"  style=" height: 380px; margin-top:-140px; width:310px ">
                    <h4>All Over Toppers</h4>
                    <div class="bar-chart-container"  style="width: 100%; height: 380px;">
                        <canvas id="activeQuizTopperChart"  style="width: 100%; height: 350px;"></canvas>
                    </div>
                </div>
        </div>
        <div class="stat-box" id="completionTimeDistribution" style="visibility: hidden;">
                <h2>Completion Time Distribution</h2>
                    <div class="bar-chart-container">
                        <canvas id="completionTimeDistributionChart" width="400" height="200"></canvas>
                    </div>
                </div>

            <div class="stat-box" id="timeSpentOnEachQuestion" style="visibility: hidden;">
                <h2>Time Spent on Each Question</h2>
                <div class="bar-chart-container">
                    <canvas id="timeSpentOnEachQuestionChart" width="400" height="200"></canvas>
                </div>
            </div>
    </div>

    <script>

        function animateCounter(target, value, extra = ''){
            console.log(target);
            let current = 0;
            value = parseFloat(value);
            const step = value / 10; 
            const interval = setInterval(function() {
                current += step;
                if (current >= value) {
                    current = value;
                    clearInterval(interval);
                }
                target.innerText = extra+" "+current.toFixed(0); 
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
            const topperDetails = toppers.map(topper => ({
                rollNo: topper.rollNo,
                year: topper.year,
                section: topper.section,
                department: topper.department
            }));

            // Destroy previous chart instance only if it exists
            if (window.activeQuizTopperChart && typeof window.activeQuizTopperChart.destroy === 'function') {
                window.activeQuizTopperChart.destroy();
            }

            // Create new bar chart
            window.activeQuizTopperChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels, // Names of top performers
                    datasets: [{
                        label: 'Top 3 Performers',
                        data: percentages, // Percentage scores
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
                            max: 100, 
                            ticks: {
                                stepSize: 10
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                // Customizing tooltips to show additional topper information
                                label: function(tooltipItem) {
                                    const index = tooltipItem.dataIndex; // Get the index of the hovered bar
                                    const topper = topperDetails[index]; // Get the corresponding topper details
                                    const details = [
                                        `Name: ${labels[index]}`,
                                        `Roll No: ${topper.rollNo}`,
                                        `Year: ${topper.year}`,
                                        `Section: ${topper.section}`,
                                        `Department: ${topper.department}`
                                    ];
                                    return details;
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderCompletionTimeDistribution() {
    document.getElementById("completionTimeDistribution").style.visibility = "visible";

    const canvas = document.getElementById("completionTimeDistributionChart");

    if (window.completionTimeDistributionChart && typeof window.completionTimeDistributionChart.destroy === "function") {
        window.completionTimeDistributionChart.destroy();
    }

    const ctx = canvas.getContext("2d");

    
    times = [10, 15, 20, 25]; // Time intervals
    frequency = [5, 15, 25, 10] ;// Number of students in each time interval
    window.completionTimeDistributionChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: times, // Example: [10, 15, 20, 25]
            datasets: [{
                label: "Number of Students",
                data: frequency, // Example: [5, 15, 25, 10]
                backgroundColor: "#9b59b6",
                borderColor: "#8e44ad",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: "Time Taken (min)" } },
                y: { beginAtZero: true, title: { display: true, text: "Frequency" } }
            }
        }
    });
}

function renderTimeSpentOnEachQuestion() {
    document.getElementById("timeSpentOnEachQuestion").style.visibility = "visible";
    const canvas = document.getElementById("timeSpentOnEachQuestionChart");

    if (window.timeSpentOnEachQuestionChart && typeof window.timeSpentOnEachQuestionChart.destroy === "function") {
        window.timeSpentOnEachQuestionChart.destroy();
    }
    const questions = ["1", "2", "3", "4", "5"];// Question numbers
    const timeSpent = [30, 45, 60, 25, 40] ;// Average time in seconds spent per question
    const ctx = canvas.getContext("2d");

    window.timeSpentOnEachQuestionChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: questions, // Example: ["1", "2", "3"]
            datasets: [{
                label: "Time Spent (s)",
                data: timeSpent, // Example: [30, 45, 60]
                borderColor: "#2ecc71",
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: "Question" } },
                y: { beginAtZero: true, title: { display: true, text: "Avg. Time per Question (s)" } }
            }
        }
    });
}


        // statistics
        fetch('fetchStats.php')
            .then(response => response.text()) // Change `.json()` to `.text()`
            .then(text => {
                console.log('Raw response:', text); // Debugging response content
                return JSON.parse(text); // Manually parse JSON
            })
            .then(data => {
                // Handle counters
                console.log('data', data);
                const totalQuizzesElement = document.getElementById('totalQuizzes');
                const averagePerformance = document.getElementById('averagePerformance');
                const totalStudentsElement = document.getElementById('totalStudents');
                const bestQuizElement = document.getElementById('bestQuiz');
                
                // Animate total quizzes counter
                animateCounter(totalQuizzesElement, data.totalQuizzes);
                animateCounter(totalStudentsElement, data.totalStudents);
                animateCounter(bestQuizElement, data.bestQuizRate, data.bestQuizName);
                
                // Animate pass percentage counter
                averagePerformance.innerText = '0%';
                let currentPercentage = 0;
                const passStep = data.average / 10;
                const passInterval = setInterval(function() {
                    currentPercentage += passStep;
                    if (currentPercentage >= data.average) {
                        currentPercentage = data.average;
                        clearInterval(passInterval);
                    }
                    averagePerformance.innerText = currentPercentage.toFixed(2) + '%';
                }, 30);

                // Update toppers section
                const toppersContainer = document.getElementById('all-time-toppers');
                toppersContainer.innerHTML = ''; 

                Object.entries(data.allTimeToppers).forEach(([year, topper]) => {
                    const yearDiv = document.createElement('div');

                    const yearHeader = document.createElement('h5');
                    yearHeader.textContent = `${year} Year`;
                    yearDiv.appendChild(yearHeader);

                    const topperContainer = document.createElement('div');
                    topperContainer.classList.add('topper-container');

                    const profileImg = document.createElement('img');
                    profileImg.src = topper.avatar ? topper.avatar : 'uploads/49/download(2).jpg';
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

                // pie Chart
                const ctx = document.getElementById("pieChart").getContext("2d");
                const labels = Object.keys(data.percentageRange);
                const percentages = Object.values(data.percentageRange);
                window.pieChart =new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "Score Distribution",
                            data: percentages,
                            backgroundColor: ["rgba(22, 77, 79, 0.63)", '#13274F', '#34495e', 'black','rgba(108, 145, 210, 0.5)']
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });

                if(data.toppers.length > 0){
                    renderToppersChart(data.toppers);
                }

                // Line Chart Data for average scores
                const realTimeCtx = document.getElementById('realTimeChart').getContext('2d');
                const realTimeChart = new Chart(realTimeCtx, {
                    type: 'line',
                    data: {
                        labels: data.avgScores.labels,  
                        datasets: [{
                            label: 'Average Score',
                            data: data.avgScores.data,  
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
                                            if (!isNaN(numericScore)) {
                                                return 'Score: ' + numericScore.toFixed(2);
                                            } else {
                                                return 'Score: N/A';  
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching stats:', error));


            // when dropDown value changes
            function fetchQuizDetails() {
                const quiz = document.getElementById('quiz').value;
                const year = document.getElementById('year').value;
                const section = document.getElementById('section').value;
                const department = document.getElementById('department').value;

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
                    document.getElementById('totalQuizzes').innerText = data.totalQuizzes;
                    document.getElementById('totalStudents').innerText = data.totalStudents;
                    document.getElementById('averagePerformance').innerText = data.averagePerformance + "%";
                    document.getElementById('bestQuiz').innerText = data.bestQuiz;
                    
                    // Update charts
                    renderToppersChart(data.topToppers);

                    const canvas = document.getElementById('pieChart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    const labels = Object.keys(data.scoreTrend);
                    const percentages = Object.values(data.scoreTrend);

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
                            responsive: true
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));

                renderTimeSpentOnEachQuestion();
                renderCompletionTimeDistribution();
            }
    </script>
</body>
</html>
