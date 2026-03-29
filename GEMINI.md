# GEMINI.md - Intelligent Career Guidance System

## Project Overview
An AI-driven career assessment and recommendation platform that helps students and professionals discover their perfect career path. The system features a 20-question intelligent assessment with weighted scoring, personalized recommendations, and an admin dashboard for content and user management.

- **Primary Technologies:** PHP 8.2+, MySQL 8.0+, HTML5, CSS3 (Custom Properties), JavaScript (Vanilla).
- **Architecture:** Multi-page Application (MPA) with a structured admin panel and assets directory.
- **Key Features:**
    - Secure User Authentication (bcrypt hashing, CSRF protection).
    - User Profile setup (education, stream, skills, interests).
    - Career Assessment with 20 questions and weighted scoring logic.
    - Smart Career Recommendations with percentage matching.
    - Admin Dashboard for analytics, career management, and question management.
    - Modern UI with Light/Dark theme support and responsive design.

## Building and Running
The project is a standard PHP application and does not require a build step.

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- A web server like Apache or Nginx (XAMPP recommended for local development).

### Installation and Setup
1. **Clone the repository** and move it to your web server's root directory (e.g., `htdocs`).
2. **Database Setup:**
    - Create a database named `intelegence`.
    - Import the `intelegence.sql` file located in the root directory.
3. **Configuration:**
    - Update `config.php` with your database credentials (host, name, user, password).
    - **Note:** Ensure consistency between the database connection method (PDO vs MySQLi). Currently, `config.php` provides a PDO instance via `getDBConnection()`, while many files expect a MySQLi `$conn` variable.

### Running the Application
- Start your web server and MySQL service.
- Navigate to `http://localhost/careerGuidenceSystem` (or your configured URL).
- Admin panel is available at `http://localhost/careerGuidenceSystem/admin/`.

## Development Conventions
- **Coding Style:**
    - PHP: Uses standard procedural and functional approaches. Prefers `require_once` for configuration and includes.
    - Security: Implement input sanitization (`sanitizeInput`), CSRF validation, and password hashing (`password_hash`).
    - CSS: Modern CSS using variables (`:root`) and a modular approach. Follows a `style.css` based system for all components.
- **Database Access:**
    - The project is transitioning towards PDO with prepared statements (see `config.php`). New developments should use the `getDBConnection()` function to maintain security.
- **Contribution Guidelines:**
    - Always test assessments and results logic after modifying questions or career profiles.
    - Ensure all administrative actions are protected by `requireAdmin()`.
    - Maintain consistency in the CSS variable naming conventions for theme support.

## Project Structure
- `/admin/`: Contains all administrative pages (dashboard, user/career/question management).
- `/assets/`: Static assets (CSS, JS, images).
- `/config.php`: Central configuration for database, sessions, and security.
- `/assessment.php`: Core logic for the career assessment test.
- `/process-assessment.php`: Logic for calculating and saving test results.
- `/intelegence.sql`: Complete database schema and initial data.
