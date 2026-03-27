-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2024 at 05:01 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `dart_club`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE OR REPLACE TABLE `applications` (
  `app_id` int(11) NOT NULL AUTO_INCREMENT,
  `app_path` text NOT NULL,
  `app_creationDate` datetime DEFAULT current_timestamp(),
  `isApproved` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`app_id`, `app_path`, `app_creationDate`) VALUES
(13, '../files/applications/1714460938Confirm Withdrawal.PNG', '2024-04-30 10:08:58'),
(15, '../files/applications/1714460967Confirm Program.PNG', '2024-04-30 10:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE OR REPLACE TABLE `blogs` (
  `blog_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_title` varchar(255) DEFAULT NULL,
  `blog_content` text DEFAULT NULL,
  `author_user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`blog_id`),
  KEY `idx_blogs_author_user` (`author_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE OR REPLACE TABLE `media` (
  `med_id` int(11) NOT NULL AUTO_INCREMENT,
  `med_file` blob DEFAULT NULL,
  `med_creationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`med_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE OR REPLACE TABLE `players` (
  `plr_idNum` int(11) NOT NULL AUTO_INCREMENT,
  `plr_name` varchar(50) DEFAULT NULL,
  `plr_surname` varchar(50) DEFAULT NULL,
  `plr_creationDate` datetime DEFAULT current_timestamp(),
  `plr_app` int(11) DEFAULT NULL,
  `plr_address` text DEFAULT NULL,
  `plr_dob` date DEFAULT NULL,
  `plr_mother` varchar(50) DEFAULT NULL,
  `plr_father` varchar(50) DEFAULT NULL,
  `plr_pob` varchar(100) DEFAULT NULL,
  `plr_phone` varchar(20) DEFAULT NULL,
  `plr_username` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`plr_idNum`),
  KEY `idx_players_user_id` (`user_id`),
  FOREIGN KEY (`plr_app`) REFERENCES `applications` (`app_id`) ON DELETE SET NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`plr_idNum`, `plr_name`, `plr_surname`, `plr_creationDate`, `plr_app`, `plr_address`, `plr_dob`, `plr_mother`, `plr_father`, `plr_pob`, `plr_phone`, `plr_username`) VALUES
(1111, '1', '1', '2024-04-25 20:11:51', NULL, '1', '1111-11-11', '1', '1', '1', '1', '1'),
(2222, '2', '2', '2024-04-25 20:12:45', NULL, '2', '2222-02-22', '2', '2', '2', '2', '2'),
(3333, '3', '3', '2024-04-29 19:39:48', NULL, '3', '3333-03-03', '3', '3', '3', '3', '3'),
(1231232111, '1013201203', '1123123123', '2024-04-30 09:50:38', NULL, '1', '1111-01-11', '1', '1', '1', '1', '1'),
(2147483647, '1111', '1111', '2024-05-02 17:54:50', NULL, '1111', '1111-11-11', '1111', '1111', '1111', '1111', '1111');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE OR REPLACE TABLE `tournaments` (
  `tour_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_title` varchar(100) NOT NULL,
  `tour_creationDate` datetime NOT NULL DEFAULT current_timestamp(),
  `tour_endDate` datetime NOT NULL,
  `tour_type` varchar(20) NOT NULL,
  `group_count` int(11) DEFAULT NULL,
  `advancers_per_group` int(11) DEFAULT NULL,
  PRIMARY KEY (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_players`
--

CREATE OR REPLACE TABLE `tournament_players` (
  `tp_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `plr_id` int(11) NOT NULL,
  `player_status` varchar(20) NOT NULL DEFAULT 'Active',
  `group_number` int(11) DEFAULT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tp_id`),
  UNIQUE KEY `tour_player_unique` (`tour_id`, `plr_id`),
  FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plr_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_standings`
--

CREATE OR REPLACE TABLE `tournament_standings` (
  `standing_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `matches_played` int(11) NOT NULL DEFAULT 0,
  `matches_won` int(11) NOT NULL DEFAULT 0,
  `matches_lost` int(11) NOT NULL DEFAULT 0,
  `matches_drawn` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 0,
  `leg_difference` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`standing_id`),
  UNIQUE KEY `tour_player_standing_unique` (`tour_id`, `player_id`),
  FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE OR REPLACE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(80) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` varchar(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `email`, `password`, `user_role`) VALUES
(1, '1', '1@1.com', '00BeYWwdjZP2E', 'player'),
(2, '1111', '1111@1111.1111', '00BeYWwdjZP2E', 'player');

ALTER TABLE `players`
  ADD CONSTRAINT `fk_players_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `blogs`
  ADD CONSTRAINT `fk_blogs_author_user` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Table structure for table `matches`
--

CREATE OR REPLACE TABLE `matches` (
  `match_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `match_date` date NOT NULL,
  `match_time` time NOT NULL,
  `player1_id` int(11) DEFAULT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `player1_score` int(11) DEFAULT NULL,
  `player2_score` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `next_match_id` int(11) DEFAULT NULL,
  `position_in_next` int(2) DEFAULT 1,
  `match_status` varchar(20) NOT NULL DEFAULT 'Scheduled',
  `match_notes` text DEFAULT NULL,
  `bracket` varchar(1) DEFAULT NULL,
  `group_number` int(11) DEFAULT NULL,
  `loser_next_match_id` int(11) DEFAULT NULL,
  `loser_position_in_next` int(2) DEFAULT NULL,
  PRIMARY KEY (`match_id`),
  FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  FOREIGN KEY (`player1_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE,
  FOREIGN KEY (`player2_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE,
  FOREIGN KEY (`winner_id`) REFERENCES `players` (`plr_idNum`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_legs`
--

CREATE OR REPLACE TABLE `match_legs` (
  `leg_id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `leg_number` int(11) NOT NULL,
  `player1_score` int(11) NOT NULL,
  `player2_score` int(11) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `leg_notes` text DEFAULT NULL,
  PRIMARY KEY (`leg_id`),
  FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  FOREIGN KEY (`winner_id`) REFERENCES `players` (`plr_idNum`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
