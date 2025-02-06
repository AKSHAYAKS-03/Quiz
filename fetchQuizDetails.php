<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: index.php');
    exit;
}

if ($conn->connect_errno) {
    echo "Failed to connect to MySQL: " . $conn->connect_error;
    exit();
}

$quiz = $_POST['quiz'];
$year = $_POST['year'];
$section = $_POST['section'];
$department = $_POST['department'];

$query = "SELECT COUNT(DISTINCT QuizId) AS totalQuizzes, COUNT(DISTINCT rollNo) AS totalStudents, 
                 AVG(Score) AS averagePerformance, 
                 MAX(Score) AS bestQuiz
          FROM student WHERE 1";

if ($quiz !== 'all') {
    $query .= " AND QuizId = '$quiz'";
}
if ($year !== 'all') {
    $query .= " AND Year = '$year'";
}
if ($section !== 'all') {
    $query .= " AND Section = '$section'";
}
if ($department !== 'all') {
    $query .= " AND Department = '$department'";
}

$result = $conn->query($query);
$data = $result->fetch_assoc();

// Fetch top performers
$topperQuery = "SELECT name AS name, RollNo AS rollNo, Year AS year, Section AS section, Department AS department, Score AS percentage 
                FROM student 
                WHERE 1";

if ($quiz !== 'all') {
    $topperQuery .= " AND QuizId = '$quiz'";
}
if ($year !== 'all') {
    $topperQuery .= " AND Year = '$year'";
}
if ($section !== 'all') {
    $topperQuery .= " AND Section = '$section'";
}
if ($department !== 'all') {
    $topperQuery .= " AND Department = '$department'";
}

$topperQuery .= " ORDER BY Score DESC LIMIT 3";

$topperResult = $conn->query($topperQuery);
$topToppers = [];

while ($row = $topperResult->fetch_assoc()) {
    $topToppers[] = $row;
}

$data['topToppers'] = $topToppers;

// piechart
$scoreDistributionQuery = "SELECT 
                            SUM(CASE WHEN Score BETWEEN 0 AND 29 THEN 1 ELSE 0 END) AS range_0_29,
                            SUM(CASE WHEN Score BETWEEN 30 AND 49 THEN 1 ELSE 0 END) AS range_30_49,
                            SUM(CASE WHEN Score BETWEEN 50 AND 74 THEN 1 ELSE 0 END) AS range_50_74,
                            SUM(CASE WHEN Score BETWEEN 75 AND 89 THEN 1 ELSE 0 END) AS range_75_89,
                            SUM(CASE WHEN Score BETWEEN 90 AND 100 THEN 1 ELSE 0 END) AS range_90_100
                        FROM student WHERE 1";
$scoreDistributionQuery = "SELECT avg(percentage) as percentageRange FROM student WHERE 1"; 
if ($quiz !== 'all') {
    $scoreDistributionQuery .= " AND QuizId = '$quiz'";
}
if ($year !== 'all') {
    $scoreDistributionQuery .= " AND Year = '$year'";
}
if ($section !== 'all') {
    $scoreDistributionQuery .= " AND Section = '$section'";
}
if ($department !== 'all') {
    $scoreDistributionQuery .= " AND Department = '$department'";
}
$scoreDistributionQuery .= " GROUP BY rollno";
$result = $conn->query($scoreDistributionQuery);

$ranges = ["90-100" => 0, "75-89" => 0, "50-74" => 0, "30-50" => 0, "Below 30" => 0];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $score = $row["percentageRange"];
    if ($score >= 90) {
        $ranges["90-100"]++;
    } elseif ($score >= 75) {
        $ranges["75-89"]++;
    } elseif ($score >= 50) {
        $ranges["50-74"]++;
    } elseif($score >= 30){
        $ranges["30-50"]++;
    } else {
        $ranges["Below 30"]++;
    }
    $total++;
}

// Convert counts to percentages
$percentages = [];
foreach ($ranges as $key => $count) {
    $percentages[$key] = ($total > 0) ? round(($count / $total) * 100, 2) : 0;
}


// line chart
$scoreTrendQuery = "SELECT QuizId, AVG(Score) AS avgScore 
                    FROM student WHERE 1";

if ($year !== 'all') {
    $scoreTrendQuery .= " AND Year = '$year'";
}
if ($section !== 'all') {
    $scoreTrendQuery .= " AND Section = '$section'";
}
if ($department !== 'all') {
    $scoreTrendQuery .= " AND Department = '$department'";
}

$scoreTrendQuery .= " GROUP BY QuizId ORDER BY QuizId ASC";
$trendResult = $conn->query($scoreTrendQuery);
$scoreTrendData = [];

while ($row = $trendResult->fetch_assoc()) {
    $scoreTrendData[] = $row;
}

$data['scoreTrend'] = $percentages;

echo json_encode($data);
?>
