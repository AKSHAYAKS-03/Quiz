-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3390
-- Generation Time: Jul 02, 2024 at 10:55 AM
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
-- Table structure for table `multiple_choices`
--

CREATE TABLE `multiple_choices` (
  `QuizId` int(11) NOT NULL,
  `QuestionNo` int(11) NOT NULL,
  `Question` varchar(200) NOT NULL,
  `Choice1` varchar(50) NOT NULL,
  `Choice2` varchar(50) NOT NULL,
  `Choice3` varchar(50) NOT NULL,
  `Choice4` varchar(50) NOT NULL,
  `Answer` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `multiple_choices`
--

INSERT INTO `multiple_choices` (`QuizId`, `QuestionNo`, `Question`, `Choice1`, `Choice2`, `Choice3`, `Choice4`, `Answer`) VALUES
(3, 1, 'HTML stands for', 'A', 'Hyper Text MarkUp Language', 'B', 'C', 'Hyper Text MarkUp Language'),
(3, 2, 'CSS stands for', 'Cascading Style Sheets', 'P', 'Q', 'R', 'Cascading Style Sheets'),
(3, 3, 'pHp stands for', 'X', 'Y', 'Z', 'HyperText PreProcessor', 'HyperText PreProcessor'),
(3, 4, 'AJAX stands for', 'M', 'N', 'Asynchronous  JavaScript And X', 'O', 'Asynchronous  JavaScript And X'),
(3, 5, 'JS stands for', 'JavaScript', 'K', 'L', 'M', 'JavaScript');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `multiple_choices`
--
ALTER TABLE `multiple_choices`
  ADD KEY `mc_fk` (`QuizId`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `multiple_choices`
--
ALTER TABLE `multiple_choices`
  ADD CONSTRAINT `mc_fk` FOREIGN KEY (`QuizId`) REFERENCES `quiz_details` (`Quiz_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
