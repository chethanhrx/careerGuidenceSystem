-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2026 at 05:12 PM
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
-- Database: `intelegence`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('skills','interests','personality','work_preference','aptitude') NOT NULL DEFAULT 'skills',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_options`)),
  `related_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_skills`)),
  `weight` int(11) DEFAULT 1,
  `career_relevance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`career_relevance`)),
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_questions`
--

INSERT INTO `assessment_questions` (`id`, `question_text`, `question_type`, `options`, `correct_options`, `related_skills`, `weight`, `career_relevance`, `sort_order`, `created_at`) VALUES
(1, 'Which programming languages are you familiar with?', 'skills', '[\"Python\", \"JavaScript\", \"Java\", \"C++\", \"SQL\", \"None of the above\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\"]', 1, '2026-02-03 15:37:40'),
(2, 'How comfortable are you with data analysis?', 'skills', '[\"Beginner - Basic Excel\", \"Intermediate - SQL queries, pivot tables\", \"Advanced - Python/Pandas, machine learning\", \"No experience\"]', NULL, NULL, 1, '[\"data-analyst\", \"software-developer\"]', 2, '2026-02-03 15:37:40'),
(3, 'Rate your problem-solving skills:', 'skills', '[\"I prefer following clear instructions\", \"I can solve routine problems\", \"I enjoy complex problem-solving\", \"I love algorithmic challenges\"]', NULL, NULL, 1, '[\"software-developer\", \"cybersecurity-analyst\", \"data-analyst\"]', 3, '2026-02-03 15:37:40'),
(4, 'What type of work interests you most?', 'interests', '[\"Building things (apps, websites, software)\", \"Analyzing data and finding patterns\", \"Protecting systems and solving security puzzles\", \"Creative marketing and communication\", \"Research and analysis\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\", \"digital-marketer\"]', 4, '2026-02-03 15:37:40'),
(5, 'Which activities do you enjoy?', 'interests', '[\"Coding and programming\", \"Working with spreadsheets and data\", \"Figuring out how things work\", \"Writing and content creation\", \"Teaching and helping others\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\", \"digital-marketer\"]', 5, '2026-02-03 15:37:40'),
(6, 'What is your preferred work environment?', 'work_preference', '[\"Structured office with clear routines\", \"Flexible remote work\", \"Dynamic startup with varied tasks\", \"Corporate with advancement opportunities\", \"Freelance/independent\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\", \"digital-marketer\"]', 6, '2026-02-03 15:37:40'),
(7, 'How do you handle deadlines?', 'work_preference', '[\"I plan ahead and finish early\", \"I work steadily to meet deadlines\", \"I work best under pressure\", \"I struggle with time management\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\", \"digital-marketer\"]', 7, '2026-02-03 15:37:40'),
(8, 'What is your experience with databases?', 'skills', '[\"No experience\", \"Basic SQL queries\", \"Database design and optimization\", \"Advanced: NoSQL, distributed databases\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\"]', 8, '2026-02-03 15:37:40'),
(9, 'How familiar are you with web technologies?', 'skills', '[\"Basic HTML/CSS\", \"Frontend frameworks (React, Vue)\", \"Backend development\", \"Full stack development\"]', NULL, NULL, 1, '[\"software-developer\", \"digital-marketer\"]', 9, '2026-02-03 15:37:40'),
(10, 'Your experience with cybersecurity concepts:', 'skills', '[\"No knowledge\", \"Basic awareness\", \"Hands-on with tools\", \"Professional experience\"]', NULL, NULL, 1, '[\"cybersecurity-analyst\"]', 10, '2026-02-03 15:37:40'),
(11, 'Experience with design tools:', 'skills', '[\"None\", \"Basic Canva/Figma\", \"Intermediate (Adobe suite)\", \"Professional UI/UX design\"]', NULL, NULL, 1, '[\"ui-ux-designer\", \"digital-marketer\"]', 11, '2026-02-03 15:37:40'),
(12, 'How comfortable are you with statistics?', 'skills', '[\"Not comfortable\", \"Basic concepts\", \"Confident with regression\", \"Advanced statistical modeling\"]', NULL, NULL, 1, '[\"data-analyst\"]', 12, '2026-02-03 15:37:40'),
(13, 'Experience with version control (Git):', 'skills', '[\"Never used\", \"Basic commands\", \"Regular user\", \"Expert with branching/merging\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\"]', 13, '2026-02-03 15:37:40'),
(14, 'Knowledge of cloud platforms:', 'skills', '[\"None\", \"Basic AWS/Azure\", \"Deployed applications\", \"Architecture design\"]', NULL, NULL, 1, '[\"software-developer\", \"cybersecurity-analyst\"]', 14, '2026-02-03 15:37:40'),
(15, 'How would you rate your mathematical/statistical skills?', 'skills', '[\"Poor\", \"Average (basic math)\", \"Good (algebra, statistics)\", \"Excellent (calculus, advanced stats)\"]', NULL, NULL, 1, '[\"data-analyst\", \"software-developer\"]', 15, '2026-02-03 15:37:40'),
(16, 'How comfortable are you with Linux/Unix systems?', 'skills', '[\"Never used\", \"Basic commands\", \"Regular user\", \"System administration\"]', NULL, NULL, 1, '[\"cybersecurity-analyst\", \"software-developer\"]', 16, '2026-02-03 15:37:40'),
(17, 'Experience with machine learning/AI?', 'skills', '[\"None\", \"Basic concepts\", \"Used ML libraries\", \"Built ML models\"]', NULL, NULL, 1, '[\"data-analyst\", \"software-developer\"]', 17, '2026-02-03 15:37:40'),
(18, 'Proficiency in design and prototyping tools?', 'skills', '[\"None\", \"Basic Canva/PowerPoint\", \"Intermediate (Figma, Adobe XD)\", \"Professional (Sketch, prototyping)\"]', NULL, NULL, 1, '[\"ui-ux-designer\", \"digital-marketer\"]', 18, '2026-02-03 15:37:40'),
(19, 'Knowledge of SEO and digital analytics?', 'skills', '[\"None\", \"Basic SEO concepts\", \"Used Google Analytics\", \"Advanced SEO/SEM campaigns\"]', NULL, NULL, 1, '[\"digital-marketer\"]', 19, '2026-02-03 15:37:40'),
(20, 'How do you approach new challenges?', 'personality', '[\"Methodical research\", \"Creative experimentation\", \"Ask for help\", \"Avoid challenges\"]', NULL, NULL, 1, '[\"software-developer\", \"data-analyst\", \"cybersecurity-analyst\", \"digital-marketer\", \"ui-ux-designer\"]', 20, '2026-02-03 15:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `careers`
--

CREATE TABLE `careers` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `overview` text NOT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `salary` varchar(100) DEFAULT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles`)),
  `roadmap` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roadmap`)),
  `courses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`courses`)),
  `certs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certs`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `careers`
--

INSERT INTO `careers` (`id`, `slug`, `title`, `category`, `overview`, `skills`, `salary`, `roles`, `roadmap`, `courses`, `certs`, `created_at`, `updated_at`) VALUES
(1, 'software-developer', 'Software Developer', 'Technology', 'Design, build, and maintain software applications.', '[\"JavaScript\", \"Python\", \"Problem solving\", \"Git\", \"APIs\"]', '₹4–25 LPA', '[\"Frontend Developer\", \"Backend Developer\", \"Full Stack Developer\"]', '[{\"title\": \"Learn fundamentals\", \"desc\": \"Pick a language and learn OOP concepts\"}, {\"title\": \"Build projects\", \"desc\": \"Create portfolio projects to showcase skills\"}, {\"title\": \"Master frameworks\", \"desc\": \"Learn popular frameworks like React or Django\"}, {\"title\": \"Prepare for interviews\", \"desc\": \"Practice DSA and system design\"}]', '[{\"name\": \"CS50\", \"platform\": \"edX\", \"url\": \"https://www.edx.org/cs50\"}, {\"name\": \"Full Stack Open\", \"platform\": \"University of Helsinki\", \"url\": \"https://fullstackopen.com/\"}]', '[\"AWS Certified Developer\", \"Microsoft Certified: Azure Developer Associate\"]', '2026-02-03 14:51:50', '2026-02-04 14:50:41'),
(2, 'data-analyst', 'Data Analyst', 'Technology', 'Collect, clean, and analyze data to support business decisions.', '[\"SQL\", \"Excel\", \"Tableau\", \"Python\", \"Statistics\", \"Data Visualization\"]', '₹3–18 LPA', '[\"Data Analyst\", \"Business Analyst\", \"Reporting Analyst\"]', '[{\"title\": \"Excel & SQL\", \"desc\": \"Master pivot tables, VLOOKUP, and SQL queries\"}, {\"title\": \"Learn Python basics\", \"desc\": \"Pandas, NumPy for data manipulation\"}, {\"title\": \"Data visualization\", \"desc\": \"Learn Tableau/Power BI for dashboards\"}, {\"title\": \"Statistics fundamentals\", \"desc\": \"Understand hypothesis testing and regression\"}]', '[{\"name\": \"Google Data Analytics\", \"platform\": \"Coursera\", \"url\": \"https://www.coursera.org/professional-certificates/google-data-analytics\"}, {\"name\": \"Data Analysis with Python\", \"platform\": \"freeCodeCamp\", \"url\": \"https://www.freecodecamp.org/learn/data-analysis-with-python/\"}]', '[\"Google Data Analytics Certificate\", \"Microsoft Certified: Data Analyst Associate\"]', '2026-02-03 14:51:50', '2026-02-04 14:50:41'),
(3, 'cybersecurity-analyst', 'Cybersecurity Analyst', 'Technology', 'Protect systems and networks from cyber threats.', '[\"Network Security\", \"Linux\", \"Risk Assessment\", \"Incident Response\", \"Firewalls\"]', '₹5–30 LPA', '[\"Security Analyst\", \"Penetration Tester\", \"Security Engineer\"]', '[{\"title\": \"Networking basics\", \"desc\": \"Learn TCP/IP, DNS, HTTP protocols\"}, {\"title\": \"Operating systems\", \"desc\": \"Master Linux command line and Windows security\"}, {\"title\": \"Security tools\", \"desc\": \"Learn Wireshark, Metasploit, Nmap\"}, {\"title\": \"Certification prep\", \"desc\": \"Prepare for Security+, CEH, or CISSP\"}]', '[{\"name\": \"Introduction to Cybersecurity\", \"platform\": \"Cisco\", \"url\": \"https://www.netacad.com/courses/introduction-cybersecurity\"}, {\"name\": \"Cybersecurity Specialization\", \"platform\": \"Coursera\", \"url\": \"https://www.coursera.org/specializations/cybersecurity\"}]', '[\"CompTIA Security+\", \"Certified Ethical Hacker (CEH)\", \"CISSP\"]', '2026-02-03 14:51:50', '2026-02-04 14:50:41'),
(4, 'digital-marketer', 'Digital Marketer', 'Marketing', 'Promote brands and products through digital channels.', '[\"SEO\", \"Social Media\", \"Content Marketing\", \"Google Analytics\", \"Email Marketing\"]', '₹3–20 LPA', '[\"SEO Specialist\", \"Social Media Manager\", \"Content Marketer\", \"PPC Specialist\"]', '[{\"title\": \"Foundation\", \"desc\": \"Learn marketing fundamentals and digital channels\"}, {\"title\": \"Content creation\", \"desc\": \"Master copywriting and visual content\"}, {\"title\": \"Analytics\", \"desc\": \"Learn Google Analytics and data interpretation\"}, {\"title\": \"Specialization\", \"desc\": \"Choose SEO, PPC, or social media focus\"}]', '[{\"name\": \"Digital Marketing Specialization\", \"platform\": \"Coursera\", \"url\": \"https://www.coursera.org/specializations/digital-marketing\"}, {\"name\": \"Google Digital Garage\", \"platform\": \"Google\", \"url\": \"https://learndigital.withgoogle.com/digitalgarage\"}]', '[\"Google Ads Certification\", \"HubSpot Content Marketing Certification\", \"Facebook Blueprint\"]', '2026-02-03 14:51:50', '2026-02-04 14:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `is_admin`, `created_at`) VALUES
(1, 'Admin User', 'admin@careerguide.com', '$2y$10$wwCMR/6woVaLz3BcDPvkV.DwgmncYjRvG4JOr6sn0emq9toTWgkIq', 1, '2026-02-03 14:51:51'),
(2, 'vinay', 'thanujchadddssndu@gmail.com', '$2y$10$sgxPmDMhjpIbsUh/qHQQouArrXNp2LnA/GYYv4bb8sFRqyvpjBpye', 0, '2026-02-03 15:11:09'),
(3, 'nithinn', 'nithin@gmail.com', '$2y$10$wwCMR/6woVaLz3BcDPvkV.DwgmncYjRvG4JOr6sn0emq9toTWgkIq', 0, '2026-02-04 14:18:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_careers`
--

CREATE TABLE `user_careers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `career_id` int(11) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `notes` text DEFAULT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `education` varchar(50) DEFAULT NULL,
  `stream` varchar(50) DEFAULT NULL,
  `career_goal` varchar(50) DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `education`, `stream`, `career_goal`, `skills`, `interests`, `created_at`, `updated_at`) VALUES
(1, 2, 'degree', 'cs', 'job', '[\"Problem solving\",\"Python\",\"JavaScript\"]', '[]', '2026-02-03 15:15:29', '2026-02-03 15:15:29'),
(2, 3, 'postgrad', 'commerce', 'higher_studies', '[\"Communication\",\"Problem solving\",\"Writing\",\"Excel\"]', '[\"Technology\",\"Design\"]', '2026-02-04 14:18:59', '2026-02-04 15:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_skills`
--

CREATE TABLE `user_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `proficiency` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `years_experience` decimal(3,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_skills`
--

INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`, `proficiency`, `years_experience`, `created_at`, `updated_at`) VALUES
(1, 2, 'Problem solving', 'beginner', 0.0, '2026-02-03 15:15:29', '2026-02-03 15:15:29'),
(2, 2, 'Python', 'beginner', 0.0, '2026-02-03 15:15:29', '2026-02-03 15:15:29'),
(3, 2, 'JavaScript', 'beginner', 0.0, '2026-02-03 15:15:29', '2026-02-03 15:15:29'),
(4, 3, 'Communication', 'beginner', 0.0, '2026-02-04 14:19:29', '2026-02-04 14:19:29'),
(5, 3, 'Problem solving', 'beginner', 0.0, '2026-02-04 14:19:29', '2026-02-04 14:19:29'),
(6, 3, 'Writing', 'beginner', 0.0, '2026-02-04 15:13:07', '2026-02-04 15:13:07'),
(7, 3, 'Excel', 'beginner', 0.0, '2026-02-04 15:13:07', '2026-02-04 15:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_tests`
--

CREATE TABLE `user_tests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_type` varchar(50) NOT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`results`)),
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tests`
--

INSERT INTO `user_tests` (`id`, `user_id`, `test_type`, `results`, `completed_at`) VALUES
(1, 2, 'career_assessment', '{\"scores\":{\"software-developer\":37,\"data-analyst\":33,\"digital-marketer\":27,\"cybersecurity-analyst\":22,\"ui-ux-designer\":10},\"suggested_careers\":[\"software-developer\",\"data-analyst\",\"digital-marketer\"],\"assessment_date\":\"2026-02-03 17:17:36\",\"total_questions\":20,\"answers\":{\"1\":2,\"2\":3,\"3\":0,\"4\":2,\"5\":3,\"6\":1,\"7\":2,\"8\":0,\"9\":1,\"10\":0,\"11\":3,\"12\":1,\"13\":0,\"14\":2,\"15\":2,\"16\":0,\"17\":3,\"18\":2,\"19\":1,\"20\":1}}', '2026-02-03 16:17:36'),
(2, 2, 'career_assessment', '{\"scores\":{\"software-developer\":35,\"data-analyst\":35,\"cybersecurity-analyst\":22,\"digital-marketer\":17,\"ui-ux-designer\":5},\"suggested_careers\":[\"software-developer\",\"data-analyst\",\"cybersecurity-analyst\"],\"assessment_date\":\"2026-02-03 17:31:16\",\"total_questions\":20,\"answers\":{\"1\":3,\"2\":2,\"3\":0,\"4\":0,\"5\":2,\"6\":3,\"7\":1,\"8\":1,\"9\":0,\"10\":0,\"11\":0,\"12\":3,\"13\":3,\"14\":1,\"15\":1,\"16\":2,\"17\":1,\"18\":2,\"19\":1,\"20\":1},\"overall_score\":23,\"insights\":[\"Your assessment shows potential in various areas.\",\"Your strongest match is with Software Developer careers.\",\"Based on your assessment, focus on developing skills in your top career matches.\"]}', '2026-02-03 16:31:16'),
(3, 3, 'career_assessment', '{\"scores\":{\"software-developer\":38,\"digital-marketer\":32,\"data-analyst\":30,\"cybersecurity-analyst\":27,\"ui-ux-designer\":13},\"suggested_careers\":[\"software-developer\",\"digital-marketer\",\"data-analyst\"],\"assessment_date\":\"2026-02-04 16:13:48\",\"total_questions\":20,\"answers\":{\"1\":0,\"2\":2,\"3\":1,\"4\":2,\"5\":1,\"6\":2,\"7\":2,\"8\":2,\"9\":2,\"10\":0,\"11\":3,\"12\":2,\"13\":0,\"14\":2,\"15\":1,\"16\":3,\"17\":0,\"18\":2,\"19\":2,\"20\":3},\"overall_score\":28,\"insights\":[\"Your assessment shows potential in various areas.\",\"Your strongest match is with Software Developer careers.\",\"Based on your assessment, focus on developing skills in your top career matches.\"]}', '2026-02-04 15:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_test_responses`
--

CREATE TABLE `user_test_responses` (
  `id` int(11) NOT NULL,
  `user_test_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selected_options`)),
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_type` (`question_type`),
  ADD KEY `idx_questions_sort` (`sort_order`);

--
-- Indexes for table `careers`
--
ALTER TABLE `careers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_careers_slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_careers`
--
ALTER TABLE `user_careers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_career` (`user_id`,`career_id`),
  ADD KEY `idx_user_careers_user` (`user_id`),
  ADD KEY `idx_user_careers_career` (`career_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_profile` (`user_id`);

--
-- Indexes for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_skill` (`user_id`,`skill_name`),
  ADD KEY `idx_user_skills_user` (`user_id`);

--
-- Indexes for table `user_tests`
--
ALTER TABLE `user_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_tests_user` (`user_id`);

--
-- Indexes for table `user_test_responses`
--
ALTER TABLE `user_test_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_responses_test` (`user_test_id`),
  ADD KEY `idx_test_responses_question` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `careers`
--
ALTER TABLE `careers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_careers`
--
ALTER TABLE `user_careers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_tests`
--
ALTER TABLE `user_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_test_responses`
--
ALTER TABLE `user_test_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_careers`
--
ALTER TABLE `user_careers`
  ADD CONSTRAINT `user_careers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_careers_ibfk_2` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tests`
--
ALTER TABLE `user_tests`
  ADD CONSTRAINT `user_tests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_test_responses`
--
ALTER TABLE `user_test_responses`
  ADD CONSTRAINT `user_test_responses_ibfk_1` FOREIGN KEY (`user_test_id`) REFERENCES `user_tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_test_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
