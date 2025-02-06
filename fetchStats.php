<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once 'core_db.php';

     session_start();

    // Check if the user is logged in
    if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
        header('Location: index.php');
        exit;
    }

    // Query to get the total number of quizzes
    $totalQuizzesQuery = "SELECT COUNT(*) AS total_quizzes FROM quiz_Details";
    $totalQuizzesResult = $conn->query($totalQuizzesQuery);
    $totalQuizzes = $totalQuizzesResult->fetch_assoc()['total_quizzes'];

    // Query to get the average pass percentage
    $averageQuery = "SELECT AVG(Percentage) AS average FROM student";
    $averageResult = $conn->query($averageQuery);
    $average = $averageResult->fetch_assoc()['average'];

    $totalStudentsQuery = 'SELECT count(*) as NoOfStudents FROM users';
    $totalStudentResult = $conn->query($totalStudentsQuery);
    $totalStudents = $totalStudentResult->fetch_assoc()['NoOfStudents'];

    // Query to get average scores for each quiz (for line chart)
    $avgScoresQuery = "SELECT q.QuizName, AVG(s.Percentage) AS avgScore
                    FROM student s
                    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                    GROUP BY q.Quiz_id";
    $avgScoresResult = $conn->query($avgScoresQuery);

    $bestQuizName = null;
    $bestQuizRate = 0;
    $labels = [];
    $data = [];
    while ($row = $avgScoresResult->fetch_assoc()) {
        $labels[] = $row['QuizName'];
        $data[] = $row['avgScore'];
        if($bestQuizRate<$row['avgScore']){
            $bestQuizName = $row['QuizName'];
            $bestQuizRate = $row['avgScore'];
        }
    }

    $activeQuizId = $_SESSION['active'];
    // Query to get the toppers (students with the highest score)
    $toppersQuery = "SELECT rollNo, name, AVG(percentage) AS percentage, year, section, department
                    FROM student 
                    GROUP BY rollNo 
                    ORDER BY percentage DESC, `Time` ASC 
                    LIMIT 3";
    $toppersResult = $conn->query($toppersQuery);

    $toppers = [];
    while ($row = $toppersResult->fetch_assoc()) {
        $toppers[] = [
            'rollNo' => $row['rollNo'],
            'name' => $row['name'],
            'percentage' => $row['percentage'],
            'year' => $row['year'],
            'section' => $row['section'],
            'department' => $row['department']
        ];
    }

    // all time Toppers
    $years = ['I', 'II', 'III', 'IV'];
    $performers = [];

    foreach ($years as $year) {
        $performerQuery = $conn->query("SELECT RollNo, AVG(percentage) as avg_percentage
                                        FROM student 
                                        WHERE year = '$year'
                                        GROUP BY RollNo
                                        ORDER BY AVG(percentage) DESC, MIN(Time) ASC 
                                        LIMIT 1");
            
        $performer = $performerQuery->fetch_assoc();

        if ($performer) {
            $rollNo = $performer['RollNo'];
            $avgPercentage = number_format((float)$performer['avg_percentage'], 2, '.', '');
    
            // Fetch user details
            $detailsQuery = $conn->query("SELECT * FROM users WHERE RegNo = '$rollNo' LIMIT 1");
            $details = $detailsQuery->fetch_assoc();
    
            if ($details) {
                $details['avg_percentage'] = $avgPercentage;
                $performers[$year] = $details;
            }
        }
    }

    $range = "SELECT avg(percentage) as percentageRange FROM student GROUP BY rollno"; 
    $result = $conn->query($range);

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

    header('Content-Type: application/json');
    echo json_encode([
        'totalQuizzes' => $totalQuizzes,
        'average' => round($average, 2),
        'totalStudents' => $totalStudents,
        'bestQuizName' => $bestQuizName,
        'bestQuizRate' => $bestQuizRate,
        'avgScores' => ['labels' => $labels, 'data' => $data],
        'allTimeToppers' =>  $performers,
        'percentageRange' => $percentages,
        'toppers' => $toppers
    ]);
?>
