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
$gnYear = $_POST['year'];
$section = $_POST['section'];
$department = $_POST['department'];

$whereConditions = '';

if ($quiz !== 'all') {
    $quizIdsArray = explode(',', $quiz);
    $quizIdsArray = array_map(function($id) {
        return $id === 'all' ? $id : (int)$id;  
    }, $quizIdsArray);

    if (!in_array('all', $quizIdsArray)) {
        $whereConditions .= " AND q.Quiz_Id IN (" . implode(',', $quizIdsArray) . ")";
    }
}
if ($gnYear !== 'all') {
    $whereConditions .= " AND u.Year = '$gnYear'";
}
if ($section !== 'all') {
    $whereConditions .= " AND u.Section = '$section'";
}
if ($department !== 'all') {
    $whereConditions .= " AND u.Department = '$department'";
}

// Fetch top performers 
$topperQuery = "SELECT s.RegNo AS RegNo, s.Name AS name, u.avatar AS avatar, u.Department AS department, u.Section AS section, u.Year AS year, s.percentage AS percentage 
                FROM student s
                JOIN users u ON s.RegNo = u.RegNo
                JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                WHERE 1";
$topperQuery.=$whereConditions;

$topperQuery .= " ORDER BY s.percentage DESC LIMIT 3";

$topperResult = $conn->query($topperQuery);
$topToppers = [];

while ($row = $topperResult->fetch_assoc()) {
    $topToppers[] = $row;
}

$data['topToppers'] = $topToppers;

// piechart
// Fetch score distribution with Department, Section, and Year from users table
$scoreDistributionQuery = "SELECT avg(s.percentage) as percentageRange FROM student s
                           JOIN users u ON s.RegNo = u.RegNo
                           JOIN quiz_Details q ON s.QuizId = q.Quiz_id 
                           WHERE 1"; 
$scoreDistributionQuery.=$whereConditions;

$scoreDistributionQuery .= " GROUP BY s.RegNo";
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
$data['percentages'] = $percentages;


// line chart
$scoreTrendQuery = "SELECT q.Quiz_Id, q.QuizName, AVG(s.Percentage) AS avgPercentage
                    FROM student s
                    JOIN users u ON s.RegNo = u.RegNo
                    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                    WHERE 1";
$scoreTrendQuery.=$whereConditions;
$scoreTrendQuery .= " GROUP BY q.Quiz_Id ORDER BY q.Quiz_Id ASC";
$trendResult = $conn->query($scoreTrendQuery);
$scoreTrendData = [];
$quizLabels = [];
$avgScores = [];

$bestQuizName = '';
$bestQuizRate = 0;

while ($row = $trendResult->fetch_assoc()) {
    $scoreTrendData[] = $row;
    $quizLabels[] = $row['QuizName'];  
    $avgScores[] = round($row['avgPercentage'], 2); 

    if($bestQuizRate<$row['avgPercentage']){
        $bestQuizName = $row['QuizName'];
        $bestQuizRate = $row['avgPercentage'];
    }
}

$data['scoreTrend'] = [
    'labels' => $quizLabels,
    'data' => $avgScores
];



// all time Toppers
$years = ['I', 'II', 'III', 'IV'];
if($gnYear !== 'all'){
    $years = [$gnYear];
}
$performers = [];

foreach ($years as $year) {
    $quiz_per_year = "SELECT count(DISTINCT q.quiz_id) as totalQuiz FROM student s
                      JOIN users u ON s.RegNo = u.RegNo
                      JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                      WHERE u.year = '$year'";
    $quiz_per_year = $conn->query($quiz_per_year)->fetch_assoc();
    
    $performerQuery = $conn->query("SELECT s.RegNo,u.Name, SUM(s.percentage) as avg_percentage,u.Avatar,
                                    SUM(s.Time) AS TotalTime
                                    FROM student s
                                    JOIN users u ON s.RegNo = u.RegNo
                                    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                                    WHERE u.year = '$year'".$whereConditions."
                                    GROUP BY s.RegNo
                                    ORDER BY avg_percentage DESC, TotalTime ASC
                                    LIMIT 1");
            
        $performer = $performerQuery->fetch_assoc();

        if ($performer) {
            $RegNo = $performer['RegNo'];
            $avgPercentage = number_format((float)$performer['avg_percentage']/$quiz_per_year['totalQuiz'], 2, '.', '');
    
            // Fetch user details
            $detailsQuery = $conn->query("SELECT * FROM users WHERE RegNo = '$RegNo' LIMIT 1");
            $details = $detailsQuery->fetch_assoc();
    
            if ($details) {
                $details['avg_percentage'] = $avgPercentage;
                $performers[$year] = $details;
            }
        }
    }

$data['performers'] = $performers;

// completion time chart
// Fetch the maximum time from the dataset
$maxTimeQuery = "
    SELECT MAX(TIME_TO_SEC(s.time)) AS maxTime
    FROM student s
    JOIN users u ON s.RegNo = u.RegNo
    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
    WHERE 1";
$maxTimeQuery .= $whereConditions;
$maxTimeResult = $conn->query($maxTimeQuery);
$maxTimeRow = $maxTimeResult->fetch_assoc();
$maxTime = $maxTimeRow['maxTime'];

// Determine interval based on max time
$interval = 10; 
if ($maxTime > 3600) {  
    $interval = 600; 
} elseif ($maxTime > 60) {  
    $interval = 60;
}

$completionTimeQuery = "
    SELECT FLOOR(TIME_TO_SEC(s.time) / $interval) * $interval AS timeRange, COUNT(*) AS studentCount
    FROM student s
    JOIN users u ON s.RegNo = u.RegNo
    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
    WHERE 1";
$completionTimeQuery .= $whereConditions;
$completionTimeQuery .= " GROUP BY timeRange ORDER BY timeRange";

$completionTimeResult = $conn->query($completionTimeQuery);
$completionTimeData = [];

while ($row = $completionTimeResult->fetch_assoc()) {
    if ($row['timeRange'] && $row['studentCount'] > 0) {
        $completionTimeData[] = [
            'timeRange' => (int) $row['timeRange'],
            'studentCount' => (int) $row['studentCount']
        ];
    }
}
$data['completionTimeData'] = $completionTimeData;


// performance average
$performanceQuery = "SELECT avg(s.percentage) as percentage FROM student s
                           JOIN users u ON s.RegNo = u.RegNo
                           JOIN quiz_Details q ON s.QuizId = q.Quiz_id 
                           WHERE 1"; 

$performanceQuery .= $whereConditions;
$performanceQuery .= " GROUP BY s.RegNo";

$data['query'] = $performanceQuery;
$result = $conn->query($performanceQuery);

// Initialize bins (0-9, 10-19, ..., 91-100)
$ranges = [];
for ($i = 0; $i <90; $i += 10) {
    $ranges["$i-" . ($i + 10)] = 0;
}
$ranges["90-100"] = 0; 

$data['fetched avg'] = '';
$data['matched'] = '';
$data['inside loop'] = '';

 // Fetch the result and categorize each student's score
while ($row = $result->fetch_assoc()) {
    $score = floatval($row["percentage"]);
    $data['fetched avg'].= $score.' ';
    foreach ($ranges as $range => &$count) {
        list($min, $max) = explode("-", $range);
        $min = floatval($min);
        $max = floatval($max);
        if ($score >= $min && $score <= $max) {
            $data['matched'].= $score.' '.$range.'\n ';
            $count++;
            break;
        }
        
        $data['inside loop'].= $score.' '.$range.' '. $count.'\n ';
    }
    unset($count); 
}

// Store the categorized data
$avgPerformance = [];
$data['range'] = '';
foreach ($ranges as $key => $count) {
    $data['range'].= $key.' '.$count.'\n ';
    $avgPerformance[$key] = $count;
}

$data['avgPerformance'] = $avgPerformance;



// comparison chart
$yearlyPerformanceQuery = "SELECT u.Year, AVG(s.percentage) AS avgPercentage
                           FROM student s
                           JOIN users u ON s.RegNo = u.RegNo
                           JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                           WHERE 1" . $whereConditions . "
                           GROUP BY u.Year
                           ORDER BY u.Year ASC";

$yearlyResult = $conn->query($yearlyPerformanceQuery);
$yearlyData = [];

while ($row = $yearlyResult->fetch_assoc()) {
    $yearlyData[] = [
        'year' => $row['Year'],
        'avgPercentage' => round($row['avgPercentage'], 2),
    ];
}

// Query to fetch average performance section-wise
$sectionPerformanceQuery = "SELECT u.Year, u.Section, AVG(s.percentage) AS avgPercentage
                            FROM student s
                            JOIN users u ON s.RegNo = u.RegNo
                            JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                            WHERE 1" . $whereConditions . "
                            GROUP BY u.Year, u.Section
                            ORDER BY u.Year ASC, u.Section ASC";

$sectionResult = $conn->query($sectionPerformanceQuery);
$sectionData = [];

while ($row = $sectionResult->fetch_assoc()) {
    if (!isset($sectionData[$row['Year']])) {
        $sectionData[$row['Year']] = [];
    }

    $sectionData[$row['Year']][] = [
        'section' => $row['Section'],
        'avgPercentage' => round($row['avgPercentage'], 2)
    ];
}

// Combine both results into one data structure
$data['yearlyPerformance'] = $yearlyData;
$data['sectionPerformance'] = $sectionData;


// counter widgets
$bestQuiz = ['bestQuizName' => $bestQuizName, 'bestQuizRate' => $bestQuizRate];

$averageQuery = "SELECT AVG(s.Percentage) AS average FROM student s
                JOIN users u ON s.RegNo = u.RegNo
                JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                WHERE 1" . $whereConditions ;
$averageResult = $conn->query($averageQuery);
$average = $averageResult->fetch_assoc()['average'];
$average = is_numeric($average) ? round($average, 2) : 0;

// Calculate total number of students from the 'users' table based on filters
$totalStudentsQuery = "SELECT COUNT(*) AS NoOfStudents FROM users u 
                       WHERE 1";
if($gnYear!=='all' ){
    $totalStudentsQuery .= " AND u.Year = '$gnYear'";
}
if($section!=='all' ){
    $totalStudentsQuery .= " AND u.Section = '$section'";
}
if($department!=='all' ){
    $totalStudentsQuery .= " AND u.Department = '$department'";
}

$totalStudentResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentResult->fetch_assoc()['NoOfStudents'];

$attendedStudentsQuery = "SELECT COUNT(DISTINCT s.RegNo) AS attended_students
                           FROM student s
                           JOIN users u ON s.RegNo = u.RegNo
                           JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                           WHERE 1" . $whereConditions;
$attendedStudentsResult = $conn->query($attendedStudentsQuery);
$attendedStudents = $attendedStudentsResult->fetch_assoc()['attended_students'];

if ($quiz === 'all' || (count($quizIdsArray)> 1) && in_array('all', $quizIdsArray)) {
    // Query to get the total number of quizzes
    $totalQuizzesQuery = "SELECT COUNT(*) AS total_quizzes FROM quiz_Details";
    $totalQuizzesResult = $conn->query($totalQuizzesQuery);
    $totalQuizzes = $totalQuizzesResult->fetch_assoc()['total_quizzes'];

    $data['c1'] = 'Total Quizzes';
    $data['c2'] = 'Total Students';
    $data['c3'] = 'Average Performance';
    $data['c4'] = 'Top Quiz';

    $data['c1_val'] = $totalQuizzes;
    $data['c2_val'] = $totalStudents;
    $data['c3_val'] = $average;
    $data['c4_val'] = $bestQuiz;
} 
elseif (count($quizIdsArray) > 1) {
    $data['c1'] = 'Attended';
    $data['c2'] = 'Not Attended';
    $data['c3'] = 'Average Performance';
    $data['c4'] = 'Top Quiz';

    $data['c1_val'] = $attendedStudents;
    $data['c2_val'] = $totalStudents - $attendedStudents;
    $data['c3_val'] = $average;
    $data['c4_val'] = $bestQuiz;
    
} else {
    $highScoreQuery = "SELECT MAX(s.Percentage) AS highScore
                       FROM student s
                       JOIN users u ON s.RegNo = u.RegNo
                       JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                       WHERE 1" . $whereConditions;

    $highScoreResult = $conn->query($highScoreQuery);
    $highScore = $highScoreResult->fetch_assoc()['highScore'];

    $data['c1'] = 'Attended';
    $data['c2'] = 'Not Attended';
    $data['c3'] = 'Average Performance';
    $data['c4'] = 'Highest Score';

    $data['c1_val'] = $attendedStudents;
    $data['c2_val'] = $totalStudents - $attendedStudents;
    $data['c3_val'] = $average;
    $data['c4_val'] = $highScore;
}

echo json_encode($data);
?>
