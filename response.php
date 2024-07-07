<?php
session_start();

$from_time1 = date('Y-m-d H:i:s');
$to_time1 = $_SESSION["end_time"];


// echo $from_time1;
// echo "<br>";
// echo $to_time1;
$timefirst = strtotime($from_time1);
$timesecond = strtotime($to_time1);
// echo "<br>";

// echo $timesecond;
// echo "<br>";

// echo $timefirst;

$difference = $timesecond - $timefirst;

$_SESSION['time'] = $difference;
// echo "<br>";

//  echo $_SESSION['time'];
//  echo "<br>";

echo gmdate("i:s", $difference);
?>
