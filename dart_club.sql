-- Dart Club baseline schema
-- Productized local-development snapshot

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `blog_reactions`;
DROP TABLE IF EXISTS `blog_comments`;
DROP TABLE IF EXISTS `blog_images`;
DROP TABLE IF EXISTS `team_matches`;
DROP TABLE IF EXISTS `match_legs`;
DROP TABLE IF EXISTS `matches`;
DROP TABLE IF EXISTS `tournament_team_players`;
DROP TABLE IF EXISTS `tournament_teams`;
DROP TABLE IF EXISTS `tournament_standings`;
DROP TABLE IF EXISTS `tournament_players`;
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `blogs`;
DROP TABLE IF EXISTS `membership_applications`;
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `tournaments`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(80) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` varchar(16) NOT NULL DEFAULT 'player',
  `membership_status` varchar(24) NOT NULL DEFAULT 'not_submitted',
  `member_since` datetime DEFAULT NULL,
  `membership_approved_at` datetime DEFAULT NULL,
  `membership_approved_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_name_unique` (`user_name`),
  KEY `idx_users_membership_reviewer` (`membership_approved_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `applications` (
  `app_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `app_path` text NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `app_creationDate` datetime DEFAULT current_timestamp(),
  `isApproved` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`app_id`),
  KEY `idx_legacy_applications_user` (`user_id`),
  CONSTRAINT `fk_legacy_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `membership_applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `application_file_path` text NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'Pending',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `idx_membership_applications_user` (`user_id`),
  KEY `idx_membership_applications_status` (`status`),
  KEY `idx_membership_applications_reviewer` (`reviewed_by_user_id`),
  CONSTRAINT `fk_membership_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_membership_applications_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `players` (
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
  UNIQUE KEY `players_user_unique` (`user_id`),
  KEY `idx_players_app` (`plr_app`),
  CONSTRAINT `fk_players_app` FOREIGN KEY (`plr_app`) REFERENCES `applications` (`app_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_players_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blogs` (
  `blog_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_title` varchar(255) DEFAULT NULL,
  `blog_content` text DEFAULT NULL,
  `author_user_id` int(11) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'published',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `moderated_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`blog_id`),
  KEY `idx_blogs_author_user` (`author_user_id`),
  KEY `idx_blogs_status` (`status`),
  KEY `idx_blogs_moderated_by` (`moderated_by_user_id`),
  CONSTRAINT `fk_blogs_author_user` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_blogs_moderated_by_user` FOREIGN KEY (`moderated_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blog_images` (
  `blog_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`blog_image_id`),
  KEY `idx_blog_images_blog` (`blog_id`),
  CONSTRAINT `fk_blog_images_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blog_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_content` text NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `idx_blog_comments_blog` (`blog_id`),
  KEY `idx_blog_comments_user` (`user_id`),
  CONSTRAINT `fk_blog_comments_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blog_reactions` (
  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` varchar(24) NOT NULL DEFAULT 'like',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reaction_id`),
  UNIQUE KEY `blog_reaction_unique` (`blog_id`,`user_id`,`reaction_type`),
  KEY `idx_blog_reactions_user` (`user_id`),
  CONSTRAINT `fk_blog_reactions_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `source_blog_id` int(11) DEFAULT NULL,
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_images_blog` (`source_blog_id`),
  KEY `idx_gallery_images_user` (`uploaded_by_user_id`),
  CONSTRAINT `fk_gallery_images_blog` FOREIGN KEY (`source_blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gallery_images_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournaments` (
  `tour_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_title` varchar(100) NOT NULL,
  `tour_creationDate` datetime NOT NULL DEFAULT current_timestamp(),
  `tour_endDate` datetime NOT NULL,
  `tour_type` varchar(32) NOT NULL,
  `format_code` varchar(32) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `registration_open_at` datetime DEFAULT NULL,
  `registration_close_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `group_count` int(11) DEFAULT NULL,
  `advancers_per_group` int(11) DEFAULT NULL,
  `team_count` int(11) DEFAULT NULL,
  `winner_player_id` int(11) DEFAULT NULL,
  `winner_team_id` int(11) DEFAULT NULL,
  `winner_label` varchar(120) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`tour_id`),
  KEY `idx_tournaments_status` (`status`),
  KEY `idx_tournaments_format` (`format_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournament_players` (
  `tp_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `plr_id` int(11) NOT NULL,
  `player_status` varchar(24) NOT NULL DEFAULT 'Registered',
  `group_number` int(11) DEFAULT NULL,
  `final_rank` int(11) DEFAULT NULL,
  `placement_label` varchar(64) DEFAULT NULL,
  `eliminated_at` datetime DEFAULT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tp_id`),
  UNIQUE KEY `tour_player_unique` (`tour_id`,`plr_id`),
  KEY `idx_tournament_players_status` (`player_status`),
  CONSTRAINT `fk_tournament_players_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournament_players_player` FOREIGN KEY (`plr_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournament_standings` (
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
  UNIQUE KEY `tour_player_standing_unique` (`tour_id`,`player_id`),
  CONSTRAINT `fk_tournament_standings_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournament_standings_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournament_teams` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `team_name` varchar(120) NOT NULL,
  `team_seed` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`team_id`),
  KEY `idx_tournament_teams_tour` (`tour_id`),
  CONSTRAINT `fk_tournament_teams_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tournament_team_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_player_unique` (`team_id`,`player_id`),
  UNIQUE KEY `tour_player_team_unique` (`tour_id`,`player_id`),
  CONSTRAINT `fk_tournament_team_players_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournament_team_players_team` FOREIGN KEY (`team_id`) REFERENCES `tournament_teams` (`team_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournament_team_players_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `matches` (
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
  `match_status` varchar(24) NOT NULL DEFAULT 'Scheduled',
  `match_notes` text DEFAULT NULL,
  `bracket` varchar(24) DEFAULT NULL,
  `group_number` int(11) DEFAULT NULL,
  `loser_next_match_id` int(11) DEFAULT NULL,
  `loser_position_in_next` int(2) DEFAULT NULL,
  PRIMARY KEY (`match_id`),
  KEY `idx_matches_tour` (`tour_id`),
  KEY `idx_matches_next` (`next_match_id`),
  CONSTRAINT `fk_matches_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_player1` FOREIGN KEY (`player1_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_player2` FOREIGN KEY (`player2_id`) REFERENCES `players` (`plr_idNum`) ON DELETE CASCADE,
  CONSTRAINT `fk_matches_winner` FOREIGN KEY (`winner_id`) REFERENCES `players` (`plr_idNum`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_next_match` FOREIGN KEY (`next_match_id`) REFERENCES `matches` (`match_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_loser_next_match` FOREIGN KEY (`loser_next_match_id`) REFERENCES `matches` (`match_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `team_matches` (
  `team_match_id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL DEFAULT 1,
  `match_date` date NOT NULL,
  `match_time` time NOT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `team1_score` int(11) DEFAULT NULL,
  `team2_score` int(11) DEFAULT NULL,
  `winner_team_id` int(11) DEFAULT NULL,
  `match_status` varchar(24) NOT NULL DEFAULT 'Scheduled',
  `match_notes` text DEFAULT NULL,
  PRIMARY KEY (`team_match_id`),
  KEY `idx_team_matches_tour` (`tour_id`),
  CONSTRAINT `fk_team_matches_tour` FOREIGN KEY (`tour_id`) REFERENCES `tournaments` (`tour_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_matches_team1` FOREIGN KEY (`team1_id`) REFERENCES `tournament_teams` (`team_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_matches_team2` FOREIGN KEY (`team2_id`) REFERENCES `tournament_teams` (`team_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_matches_winner` FOREIGN KEY (`winner_team_id`) REFERENCES `tournament_teams` (`team_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `match_legs` (
  `leg_id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `leg_number` int(11) NOT NULL,
  `player1_score` int(11) NOT NULL,
  `player2_score` int(11) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `leg_notes` text DEFAULT NULL,
  PRIMARY KEY (`leg_id`),
  CONSTRAINT `fk_match_legs_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_legs_winner` FOREIGN KEY (`winner_id`) REFERENCES `players` (`plr_idNum`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_membership_approved_by` FOREIGN KEY (`membership_approved_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `tournaments`
  ADD CONSTRAINT `fk_tournaments_winner_player` FOREIGN KEY (`winner_player_id`) REFERENCES `players` (`plr_idNum`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tournaments_winner_team` FOREIGN KEY (`winner_team_id`) REFERENCES `tournament_teams` (`team_id`) ON DELETE SET NULL;

INSERT INTO `users`
  (`user_id`, `user_name`, `email`, `password`, `user_role`, `membership_status`, `member_since`, `membership_approved_at`)
VALUES
  (1, 'admin', 'admin@test.local', 'adminpass', 'admin', 'approved', NOW(), NOW()),
  (2, 'manager', 'manager@test.local', 'managerpass', 'manager', 'approved', NOW(), NOW()),
  (3, 'member', 'member@test.local', 'memberpass', 'player', 'approved', NOW(), NOW()),
  (4, 'player', 'player@test.local', 'playerpass', 'player', 'not_submitted', NULL, NULL);

INSERT INTO `players`
  (`plr_idNum`, `plr_name`, `plr_surname`, `plr_address`, `plr_dob`, `plr_phone`, `plr_username`, `user_id`)
VALUES
  (1001, 'Mira', 'Player', 'Famagusta', '1998-03-11', '+90-533-100-1001', 'player', 4),
  (1002, 'Kemal', 'Member', 'Famagusta', '1994-01-08', '+90-533-100-1002', 'member', 3),
  (1003, 'Asli', 'Manager', 'Famagusta', '1991-07-18', '+90-533-100-1003', 'manager', 2),
  (1004, 'Lina', 'Acar', 'Famagusta', '1999-06-12', '+90-533-100-1004', 'lina.acar', NULL),
  (1005, 'Ipek', 'Kaya', 'Famagusta', '2000-09-23', '+90-533-100-1005', 'ipek.kaya', NULL),
  (1006, 'Deniz', 'Ozkan', 'Famagusta', '1997-02-17', '+90-533-100-1006', 'deniz.ozkan', NULL),
  (1007, 'Rana', 'Soyer', 'Famagusta', '1996-10-04', '+90-533-100-1007', 'rana.soyer', NULL),
  (1008, 'Emir', 'Tas', 'Famagusta', '1995-12-19', '+90-533-100-1008', 'emir.tas', NULL),
  (1009, 'Ceren', 'Kilic', 'Famagusta', '2001-04-25', '+90-533-100-1009', 'ceren.kilic', NULL),
  (1010, 'Atlas', 'Demir', 'Famagusta', '1993-08-09', '+90-533-100-1010', 'atlas.demir', NULL),
  (1011, 'Selin', 'Yilmaz', 'Famagusta', '1998-11-30', '+90-533-100-1011', 'selin.yilmaz', NULL),
  (1012, 'Baran', 'Aydin', 'Famagusta', '1992-05-06', '+90-533-100-1012', 'baran.aydin', NULL);

INSERT INTO `blogs`
  (`blog_id`, `blog_title`, `blog_content`, `author_user_id`, `status`, `created_at`, `published_at`)
VALUES
  (1, 'Welcome to the new Dart Club platform', '<p>Follow tournaments, apply for membership, and stay up to date with club news here.</p>', 1, 'published', NOW(), NOW());

COMMIT;
