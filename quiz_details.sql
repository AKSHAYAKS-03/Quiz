-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3390
-- Generation Time: Jul 02, 2024 at 10:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quizz`
--

-- --------------------------------------------------------

--
-- Table structure for table `quiz_details`
--

CREATE TABLE `quiz_details` (
  `Quiz_id` int(11) NOT NULL,
  `QuizName` varchar(50) NOT NULL,
  `NumberOfQuestions` int(11) NOT NULL,
  `TimeDuration` varchar(11) NOT NULL,
  `CreatedBy` varchar(30) NOT NULL,
  `CreatedOn` date NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(4) NOT NULL DEFAULT 0,
  `TotalMarks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `quiz_details`
--

INSERT INTO `quiz_details` (`Quiz_id`, `QuizName`, `NumberOfQuestions`, `TimeDuration`, `CreatedBy`, `CreatedOn`, `IsActive`, `TotalMarks`) VALUES
(1, 'Java', 15, '0730', 'akshaya', '2024-07-02', 0, 15),
(2, 'OS', 30, '1500', 'Suriya', '2024-07-02', 1, 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `quiz_details`
--
ALTER TABLE `quiz_details`
  ADD PRIMARY KEY (`Quiz_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
