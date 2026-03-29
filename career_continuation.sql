-- ============================================================
-- Career Continuation & Resource Recommendation System
-- Database Migration
-- Run this AFTER the main intelegence.sql has been imported.
-- ============================================================

-- --------------------------------------------------------
-- Table: courses
-- Dedicated table for online courses per career
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `platform` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `course_level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_courses_career` (`career_id`),
  KEY `idx_courses_free` (`is_free`),
  KEY `idx_courses_level` (`course_level`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: colleges
-- Colleges, institutes & training centres per career
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `colleges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `course_offered` varchar(255) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `type` enum('college','institute','training_center','bootcamp') NOT NULL DEFAULT 'institute',
  `mode` enum('online','offline','hybrid') NOT NULL DEFAULT 'offline',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_colleges_career` (`career_id`),
  KEY `idx_colleges_mode` (`mode`),
  CONSTRAINT `colleges_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: user_saved_resources
-- Allows users to bookmark courses and colleges
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_saved_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_type` enum('course','college') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_resource` (`user_id`,`resource_type`,`resource_id`),
  KEY `idx_saved_user` (`user_id`),
  CONSTRAINT `user_saved_resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- SEED DATA — Courses
-- career_id 1 = software-developer
-- career_id 2 = data-analyst
-- career_id 3 = cybersecurity-analyst
-- career_id 4 = digital-marketer
-- ============================================================

INSERT INTO `courses` (`career_id`, `title`, `platform`, `url`, `duration`, `rating`, `is_free`, `course_level`, `description`) VALUES
-- Software Developer (id=1)
(1, 'CS50: Introduction to Computer Science', 'edX (Harvard)', 'https://www.edx.org/cs50', '12 weeks', 4.9, 1, 'beginner', 'The legendary Harvard course covering C, Python, SQL, and web programming. Perfect first step for any developer.'),
(1, 'The Odin Project', 'The Odin Project', 'https://www.theodinproject.com/', 'Self-paced', 4.8, 1, 'beginner', 'A free, open-source full-stack curriculum covering HTML, CSS, JavaScript, Node.js, and Ruby on Rails.'),
(1, 'Full Stack Open 2024', 'University of Helsinki', 'https://fullstackopen.com/', '12 weeks', 4.8, 1, 'intermediate', 'Deep dive into modern web development with React, Node.js, GraphQL, TypeScript, and CI/CD practices.'),
(1, 'The Complete Web Developer Bootcamp', 'Udemy', 'https://www.udemy.com/course/the-complete-web-development-bootcamp/', '65 hours', 4.7, 0, 'beginner', 'Comprehensive bootcamp covering HTML5, CSS3, JavaScript, React, Node.js, MongoDB, and more.'),
(1, 'JavaScript: The Advanced Concepts', 'Udemy', 'https://www.udemy.com/course/advanced-javascript-concepts/', '25 hours', 4.8, 0, 'advanced', 'Master closures, prototypal inheritance, async programming, and performance optimization in JavaScript.'),
(1, 'Meta Front-End Developer Professional Certificate', 'Coursera', 'https://www.coursera.org/professional-certificates/meta-front-end-developer', '7 months', 4.6, 0, 'intermediate', 'Industry-recognized certificate from Meta covering React, UI/UX design, and front-end development.'),
(1, 'AWS Certified Developer Prep', 'A Cloud Guru', 'https://acloudguru.com/course/aws-certified-developer-associate', '20 hours', 4.7, 0, 'advanced', 'Prepare for the AWS Certified Developer — Associate exam with hands-on labs.'),

-- Data Analyst (id=2)
(2, 'Google Data Analytics Professional Certificate', 'Coursera', 'https://www.coursera.org/professional-certificates/google-data-analytics', '6 months', 4.8, 0, 'beginner', 'Job-ready program from Google covering data preparation, analysis, visualization, and R programming.'),
(2, 'Data Analysis with Python', 'freeCodeCamp', 'https://www.freecodecamp.org/learn/data-analysis-with-python/', 'Self-paced', 4.7, 1, 'beginner', 'Learn NumPy, Pandas, Matplotlib, and Seaborn through hands-on projects and certifications.'),
(2, 'SQL for Data Science', 'Coursera (UC Davis)', 'https://www.coursera.org/learn/sql-for-data-science', '4 weeks', 4.6, 0, 'beginner', 'Master SQL fundamentals, filtering, subqueries, and data manipulation for data science workflows.'),
(2, 'IBM Data Analyst Professional Certificate', 'Coursera', 'https://www.coursera.org/professional-certificates/ibm-data-analyst', '11 months', 4.7, 0, 'intermediate', 'Covers Python, SQL, Excel, Cognos Analytics, and Tableau with real-world capstone projects.'),
(2, 'Tableau Public Training', 'Tableau', 'https://www.tableau.com/learn/training/20221', 'Self-paced', 4.5, 1, 'beginner', 'Official free training from Tableau to build stunning, interactive dashboards and vizzes.'),
(2, 'Statistics with Python Specialization', 'Coursera (Michigan)', 'https://www.coursera.org/specializations/statistics-with-python', '4 months', 4.5, 0, 'intermediate', 'Covers understanding, visualizing, and making inferences from data using Python and statistical theory.'),
(2, 'Power BI Desktop for Business Intelligence', 'Udemy', 'https://www.udemy.com/course/microsoft-power-bi-up-running-with-power-bi-desktop/', '18 hours', 4.7, 0, 'intermediate', 'Master Power BI from scratch — connecting data, building models, and creating reports and dashboards.'),

-- Cybersecurity Analyst (id=3)
(3, 'Introduction to Cybersecurity', 'Cisco NetAcad', 'https://www.netacad.com/courses/introduction-cybersecurity', '7 hours', 4.7, 1, 'beginner', 'Free beginner course exploring the world of cybersecurity, online safety, and data privacy fundamentals.'),
(3, 'Google Cybersecurity Professional Certificate', 'Coursera', 'https://www.coursera.org/professional-certificates/google-cybersecurity', '6 months', 4.8, 0, 'beginner', 'Job-ready program from Google covering SIEM tools, Python automation, and incident response.'),
(3, 'CompTIA Security+ Study Guide', 'Udemy', 'https://www.udemy.com/course/securityplus/', '14 hours', 4.7, 0, 'intermediate', 'Comprehensive preparation for the CompTIA Security+ (SY0-701) certification exam.'),
(3, 'Ethical Hacking from Scratch', 'Udemy (Zaid Sabih)', 'https://www.udemy.com/course/learn-ethical-hacking-from-scratch/', '14 hours', 4.6, 0, 'intermediate', 'Learn penetration testing, network hacking, web app attacks, and social engineering techniques.'),
(3, 'Cybersecurity Specialization', 'Coursera (Maryland)', 'https://www.coursera.org/specializations/cybersecurity', '4 months', 4.6, 0, 'advanced', 'In-depth coverage of usable security, software security, cryptography, and hardware security.'),
(3, 'TryHackMe — Learning Paths', 'TryHackMe', 'https://tryhackme.com/paths', 'Self-paced', 4.9, 0, 'beginner', 'Gamified cybersecurity training with hands-on virtual labs covering blue team, red team, and SOC paths.'),
(3, 'Hack The Box Academy', 'Hack The Box', 'https://academy.hackthebox.com/', 'Self-paced', 4.8, 0, 'advanced', 'Professional-grade offensive security training with enterprise certifications (CPTS, CBBH).'),

-- Digital Marketer (id=4)
(4, 'Fundamentals of Digital Marketing', 'Google Digital Garage', 'https://learndigital.withgoogle.com/digitalgarage/course/digital-marketing', '40 hours', 4.8, 1, 'beginner', 'Free course from Google covering SEO, SEM, social media, analytics, and e-commerce. Includes certification.'),
(4, 'Meta Social Media Marketing Professional Certificate', 'Coursera', 'https://www.coursera.org/professional-certificates/facebook-social-media-marketing', '5 months', 4.7, 0, 'beginner', 'Learn social media marketing, advertising, and analytics from Meta — the leader in social platforms.'),
(4, 'SEO Training Course', 'Moz', 'https://moz.com/beginners-guide-to-seo', 'Self-paced', 4.6, 1, 'beginner', 'The comprehensive beginner''s guide to SEO from Moz, covering how search engines work, keyword research, and link building.'),
(4, 'Digital Marketing Specialization', 'Coursera (Illinois)', 'https://www.coursera.org/specializations/digital-marketing', '8 months', 4.5, 0, 'intermediate', 'Covers digital marketing analytics, SEO, social media, and 3D printing in a capstone project.'),
(4, 'Google Ads Certification Prep', 'Google Skillshop', 'https://skillshop.withgoogle.com/intl/en_ALL/category/advertising', 'Self-paced', 4.7, 1, 'intermediate', 'Official Google Ads training and certification in Search, Display, Video, Shopping, and Measurement.'),
(4, 'Email Marketing Certification Course', 'HubSpot Academy', 'https://academy.hubspot.com/courses/email-marketing', '4 hours', 4.8, 1, 'beginner', 'Free HubSpot certification covering email strategy, deliverability, design, testing, and analytics.'),
(4, 'Advanced Content Marketing', 'Udemy', 'https://www.udemy.com/course/content-marketing-strategy/', '8 hours', 4.5, 0, 'advanced', 'Master content strategy, brand storytelling, SEO content, repurposing, and content distribution.');


-- ============================================================
-- SEED DATA — Colleges & Institutes
-- ============================================================

INSERT INTO `colleges` (`career_id`, `name`, `location`, `course_offered`, `website_url`, `type`, `mode`, `description`) VALUES
-- Software Developer (id=1)
(1, 'IIT Bombay — Online Degree in Programming & DS', 'Mumbai, Maharashtra (Online)', 'BS in Programming & Data Science', 'https://www.iitbombay.org/academics/online-degree-programs', 'college', 'online', 'IIT Bombay''s fully online BS degree in Programming and Data Science — affordable, world-class education.'),
(1, 'NIIT Tech', 'Pan India + Online', 'Full Stack Development, Cloud Computing', 'https://www.niit.com/', 'training_center', 'hybrid', 'Established IT training institute with job guarantee programs in full-stack, cloud, and DevOps development.'),
(1, 'Masai School', 'Bengaluru, Karnataka (Online)', 'Full Stack Web Development', 'https://www.masaischool.com/', 'bootcamp', 'online', 'ISA-based income share bootcamp — pay only after getting a job. 6-month intensive full-stack program.'),
(1, 'GeeksforGeeks Institute', 'Online', 'DSA to Development, Full Stack', 'https://www.geeksforgeeks.org/courses/', 'institute', 'online', 'Structured programs covering Data Structures, Algorithms, system design, and full-stack development.'),
(1, 'Newton School', 'Bengaluru (Online)', 'Software Engineering', 'https://www.newtonschool.co/', 'bootcamp', 'online', 'Job-guaranteed full-stack bootcamp with live mentoring, interview prep, and industry partnerships.'),
(1, 'Coding Ninjas', 'Delhi + Online', 'DSA, Web Dev, Backend Development', 'https://www.codingninjas.com/', 'institute', 'hybrid', 'Programming education platform with structured courses, mock interviews, and placement support.'),
(1, 'IIT Delhi', 'New Delhi, Delhi', 'B.Tech in Computer Science', 'https://home.iitd.ac.in/', 'college', 'offline', 'One of the most prestigious engineering institutes in India, producing world-class software engineers.'),
(1, 'BITS Pilani', 'Pilani, Rajasthan', 'B.E. in Computer Science', 'https://www.bits-pilani.ac.in/', 'college', 'offline', 'Top private engineering college in India with strong industry connections and a modern CS curriculum.'),

-- Data Analyst (id=2)
(2, 'IIM Bangalore — Business Analytics Program', 'Bengaluru, Karnataka', 'Executive Program in Business Analytics', 'https://www.iimb.ac.in/', 'college', 'hybrid', 'Prestigious IIM Bangalore executive program combining analytics, management science, and decision making.'),
(2, 'UpGrad — Data Science Program', 'Online', 'PG Diploma in Data Science', 'https://www.upgrad.com/data-science-certifications/', 'institute', 'online', 'Industry-aligned 12-month PG program with mentoring from industry practitioners and IBM certification.'),
(2, 'Jigsaw Academy (Manipal)', 'Online', 'PG Program in Data Analytics', 'https://www.jigsawacademy.com/', 'institute', 'online', 'Analytics-focused programs with hands-on projects, career support, and placement assistance.'),
(2, 'IIIT Hyderabad — Data Science', 'Hyderabad, Telangana', 'M.Tech in Data Science', 'https://www.iiit.ac.in/', 'college', 'offline', 'Research-focused M.Tech program at IIIT Hyderabad covering ML, AI, statistics, and data engineering.'),
(2, 'Springboard India', 'Online', 'Data Analytics Career Track', 'https://www.springboard.com/courses/data-analytics-career-track/', 'institute', 'online', 'Mentor-led online learning with 1:1 sessions, job guarantee, and project-based curriculum.'),
(2, 'Indian Statistical Institute (ISI)', 'Kolkata, West Bengal', 'PG in Business Analytics (PGDBA)', 'https://www.isical.ac.in/', 'college', 'offline', 'Top-ranked joint program by ISI, IIT Kharagpur, and IIM Calcutta. The gold standard for data analysts in India.'),

-- Cybersecurity Analyst (id=3)
(3, 'IIT Madras — Cybersecurity Online Certificate', 'Chennai, Tamil Nadu (Online)', 'Certificate in Cybersecurity', 'https://onlinedegree.iitm.ac.in/', 'college', 'online', 'IIT Madras online certificate program covering network security, ethical hacking, and cryptography.'),
(3, 'Appin Technology Lab', 'Pan India', 'Ethical Hacking, Network Security', 'https://www.appinlab.com/', 'training_center', 'hybrid', 'Leading cybersecurity training institute with CEH, CISSP, and custom security training programs.'),
(3, 'EC-Council Authorized Training Centre', 'Multiple Cities + Online', 'CEH, CHFI, ECSA Certification Courses', 'https://www.eccouncil.org/', 'institute', 'hybrid', 'Official training for EC-Council certifications including the globally recognized CEH (Certified Ethical Hacker).'),
(3, 'SANS Institute', 'Online (Global)', 'GIAC Certifications, Security Training', 'https://www.sans.org/', 'institute', 'online', 'World-leading cybersecurity training organization offering GIAC certifications and intensive bootcamps.'),
(3, 'Symbiosis Institute of Technology', 'Pune, Maharashtra', 'B.Tech/M.Tech in Cybersecurity', 'https://www.sitpune.edu.in/', 'college', 'offline', 'Dedicated cybersecurity engineering programs with labs, industry partnerships, and placement records.'),
(3, 'C-DAC (Centre for Development of Advanced Computing)', 'Pune/Delhi/Bengaluru', 'PG Diploma in IT Infrastructure, Systems & Security', 'https://www.cdac.in/', 'training_center', 'offline', 'Premier R&D organization of the Ministry of Electronics offering highly respected PG diplomas in cyber security.'),

-- Digital Marketer (id=4)
(4, 'MICA Ahmedabad — Digital Marketing', 'Ahmedabad, Gujarat', 'PG Certificate in Digital Marketing', 'https://www.mica.ac.in/', 'college', 'hybrid', 'India''s premier communications school offering a rigorous digital marketing and strategy program.'),
(4, 'Digital Vidya', 'Online + Delhi/Mumbai', 'Certified Digital Marketing Master (CDMM)', 'https://www.digitalvidya.com/', 'institute', 'hybrid', 'One of India''s largest digital marketing training institutes with 40+ courses and placement support.'),
(4, 'NIIT Digital Marketing', 'Pan India', 'Advanced Digital Marketing Program', 'https://www.niit.com/en/india/digital-marketing', 'training_center', 'hybrid', 'Comprehensive program covering SEO, SEM, social media, analytics, and content marketing with certifications.'),
(4, 'Manipal ProLearn', 'Online', 'PG Diploma in Digital Marketing', 'https://www.manipalprolearn.com/', 'institute', 'online', 'Industry-oriented PG program with hands-on projects, Google certifications, and career mentorship.'),
(4, 'Xavier Institute of Communications (XIC)', 'Mumbai, Maharashtra', 'Certificate in Digital Marketing', 'https://www.xav.edu.in/', 'college', 'offline', 'Heritage communications institution with a highly practical digital marketing certificate program.'),
(4, 'Indian School of Business (ISB)', 'Hyderabad, Telangana', 'Advanced Management Programme in Marketing', 'https://www.isb.edu/', 'college', 'hybrid', 'Top-tier business school in India offering advanced analytics and digital marketing strategies for leaders.');
