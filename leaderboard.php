<?php
include 'core_db.php';
include 'header.php';
session_start();

$rollno = $_SESSION['RollNo'];
$name = $_SESSION['Name'];

$query1 = "
    SELECT Quiz_id, QuizName, QuizType, NumberOfQuestions, TimeDuration, TotalMarks, startingtime, EndTime, IsActive
    FROM quiz_details";
$allquizzes = $conn->query($query1);

$query = "SELECT Name, Score FROM student WHERE QuizId = ? ORDER BY Score DESC LIMIT 3";
$stmt = $conn->prepare($query);
?>

<div class="content">
    <div class="quiz-leaderboard-container">
        <button class="scroll-btn" id="scrollLeft">&#8592;</button>
        <div class="quiz-leaderboard" id="quizLeaderboard">
            <?php if ($allquizzes->num_rows > 0): ?>
                <?php while ($quiz = $allquizzes->fetch_assoc()): ?>
                    <?php
                        $stmt->bind_param('i', $quiz['Quiz_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $topRanks = [];
                        while ($row = $result->fetch_assoc()) {
                            $topRanks[] = $row;
                        }

                        $ranks = [1 => 'gold-bar', 2 => 'silver-bar', 3 => 'bronze-bar'];
                        $heights = [1 => 180, 2 => 150, 3 => 120];
                        $displayOrder = [2, 1, 3];
                    ?>

                    <div class="quiz-item">
                        <h3><?= htmlspecialchars($quiz['QuizName']); ?></h3>
                        <div class="leaderboard">
                            <?php foreach ($displayOrder as $rank): ?>
                                <?php 
                                    if (isset($topRanks[$rank - 1])) {
                                        $row = $topRanks[$rank - 1];
                                        if ($row['Score'] > 0) {
                                            $class = $ranks[$rank];
                                            $height = $heights[$rank];
                                        } else {
                                            // Skip if the score is 0
                                            continue;
                                        }
                                    ?>
                                    <div class="bar <?= $class ?>" style="height: <?= $height ?>px;" title="Name: <?= htmlspecialchars($row['Name']) ?>">
                                        <span class="score"><?= htmlspecialchars($row['Score']); ?></span>
                                        <!-- <span class="name"><?= htmlspecialchars($row['Name']); ?></span> -->
                                    </div>
                                <?php } ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No quizzes available.</p>
            <?php endif; ?>
        </div>
        <button class="scroll-btn" id="scrollRight">&#8594;</button>
    </div>
</div>

<style>
  body {
    background: rgb(255, 255, 255);
    font-family: Arial, sans-serif;
    text-align: center;
    margin: 0;
    padding: 0;
    max-width: 250px;
    overflow: hidden;
  }

  h2 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 10px;
  }

  .quiz-leaderboard-container {
    display: flex;
    align-items: center;
    width: 100%;
  }

  .quiz-leaderboard {
    display: flex;
    overflow-x: hidden;
    width: 100%;
  }

  .quiz-item {
    width: 60%;
    margin: 0 10px;
    padding: 20px;
    background: #fff;
    border-radius: 15px;
    text-align: center;
  }

  .leaderboard {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 5px;
    margin-top: 20px;
  }

  .bar {
    margin-top: -10px;
    min-width: 10px;
    color: white;
    font-weight: bold;
    border-radius: 5px 5px 0 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 15px;
    transition: transform 0.4s ease;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    position: relative;
  }

  .bar:hover {
    transform: scale(1.1) translateY(-10px);
  }

  .gold-bar {
    background: linear-gradient(135deg, #FFD700, #FFB300);
  }

  .silver-bar {
    background: linear-gradient(135deg, #C0C0C0, #A9A9A9);
  }

  .bronze-bar {
    background: linear-gradient(135deg, #CD7F32, #B87333);
  }

  .score {
    font-size: 24px;
    margin-bottom: 5px;
  }

  .name {
    font-size: 16px;
  }

  .scroll-btn {
    background: transparent;
    color: black;
    padding: 10px;
    border: none;
    cursor: pointer;
    overflow: hidden;
  }

  .scroll-btn:hover {
    background-color: rgba(0, 0, 0, 0.1);
  }

  /* Tooltip styles */
  .bar[title] {
    position: relative;
  }

  .bar[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 5px;
    font-size: 14px;
    white-space: nowrap;
    z-index: 10;
  }
</style>

<script>
    const scrollContainer = document.getElementById('quizLeaderboard');
    document.getElementById('scrollLeft').addEventListener('click', () => {
        scrollContainer.scrollBy({ left: -120, behavior: 'smooth' });
    });
    document.getElementById('scrollRight').addEventListener('click', () => {
        scrollContainer.scrollBy({ left: 120, behavior: 'smooth' });
    });
</script>

<?php $conn->close(); ?>
