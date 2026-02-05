<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user profile completion status
$profile_complete = false;
$profile_data = [];
$sql_profile = "SELECT * FROM user_profiles WHERE user_id = '$user_id'";
$result_profile = mysqli_query($conn, $sql_profile);
if ($result_profile && mysqli_num_rows($result_profile) > 0) {
    $profile_complete = true;
    $profile_data = mysqli_fetch_assoc($result_profile);
}

// Get test completion status and results
$test_complete = false;
$test_results = [];
$rec_count = 0;
$top_career_name = '';

$sql_test = "SELECT * FROM user_tests WHERE user_id = '$user_id' ORDER BY completed_at DESC LIMIT 1";
$result_test = mysqli_query($conn, $sql_test);
if ($result_test && mysqli_num_rows($result_test) > 0) {
    $test_complete = true;
    $test_data = mysqli_fetch_assoc($result_test);
    
    // Decode JSON results
    if (!empty($test_data['results'])) {
        $test_results = json_decode($test_data['results'], true);
        
        // Get recommendation count and top career
        if (isset($test_results['suggested_careers'])) {
            $rec_count = count($test_results['suggested_careers']);
            if ($rec_count > 0) {
                // Get the first suggested career name
                $top_career_slug = $test_results['suggested_careers'][0];
                $sql_career = "SELECT title FROM careers WHERE slug = '$top_career_slug'";
                $result_career = mysqli_query($conn, $sql_career);
                if ($result_career && mysqli_num_rows($result_career) > 0) {
                    $career_data = mysqli_fetch_assoc($result_career);
                    $top_career_name = $career_data['title'];
                }
            }
        }
    }
}

// Get saved careers count
$sql_saved = "SELECT COUNT(*) as saved_count FROM user_careers WHERE user_id = '$user_id'";
$result_saved = mysqli_query($conn, $sql_saved);
if ($result_saved) {
    $saved_data = mysqli_fetch_assoc($result_saved);
    $saved_count = $saved_data['saved_count'];
} else {
    $saved_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Dashboard specific styles */
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--color-bg, #f8f9fa);
      color: var(--color-text, #333);
      margin: 0;
      padding: 0;
    }
    
    .dashboard-container {
      display: flex;
      min-height: 100vh;
      margin-top: 60px; /* Account for fixed header */
    }
    
    .main-with-sidebar {
      display: flex;
      width: 100%;
    }
    
    /* Fixed sidebar */
    .sidebar {
      width: 250px;
      background: var(--color-surface, #fff);
      border-right: 1px solid var(--color-border, #e0e0e0);
      padding: 20px 0;
      position: fixed;
      left: 0;
      top: 60px;
      height: calc(100vh - 60px);
      overflow-y: auto;
      display: block !important; /* Force display */
    }
    
    .sidebar-brand {
      padding: 0 20px 20px;
      font-weight: 600;
      color: var(--color-text, #333);
      border-bottom: 1px solid var(--color-border, #e0e0e0);
      margin-bottom: 20px;
    }
    
    .sidebar-nav {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .sidebar-nav li {
      margin: 0;
    }
    
    .sidebar-nav a {
      display: block;
      padding: 12px 20px;
      color: var(--color-text, #333);
      text-decoration: none;
      border-left: 3px solid transparent;
      transition: all 0.2s;
    }
    
    .sidebar-nav a:hover {
      background: var(--color-surface-hover, #f5f5f5);
      color: var(--color-primary, #4a6cf7);
    }
    
    .sidebar-nav a.active {
      background: var(--color-primary-light, #e8ebff);
      color: var(--color-primary, #4a6cf7);
      border-left-color: var(--color-primary, #4a6cf7);
      font-weight: 500;
    }
    
    /* Main content area */
    .main-content {
      flex: 1;
      padding: 30px;
      margin-left: 250px; /* Same as sidebar width */
      max-width: calc(100% - 250px);
    }
    
    .dashboard-header {
      margin-bottom: 30px;
    }
    
    .dashboard-header h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
      font-weight: 700;
    }
    
    .dashboard-header p {
      color: var(--color-text-secondary, #666);
      margin: 0;
      font-size: 16px;
    }
    
    /* Dashboard cards grid */
    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .card {
      background: var(--color-surface, #fff);
      border-radius: 12px;
      border: 1px solid var(--color-border, #e0e0e0);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    
    .welcome-card {
      padding: 25px;
      margin-bottom: 30px;
    }
    
    .welcome-card h2 {
      margin: 0 0 10px 0;
      font-size: 22px;
      font-weight: 600;
    }
    
    .welcome-card p {
      color: var(--color-text-secondary, #666);
      margin: 0 0 20px 0;
      line-height: 1.5;
    }
    
    .stat-card {
      padding: 25px;
      text-align: center;
    }
    
    .stat-card .icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin: 0 auto 15px;
      background: var(--color-primary-light, #e8ebff);
      color: var(--color-primary, #4a6cf7);
    }
    
    .stat-card .value {
      font-size: 32px;
      font-weight: 700;
      color: var(--color-text, #333);
      margin: 0 0 5px 0;
    }
    
    .stat-card .label {
      font-size: 14px;
      color: var(--color-text-secondary, #666);
      margin: 0 0 15px 0;
    }
    
    .stat-card a {
      display: inline-block;
      color: var(--color-primary, #4a6cf7);
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
    }
    
    .stat-card a:hover {
      text-decoration: underline;
    }
    
    .analysis-card, .growth-card {
      padding: 25px;
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 20px;
      font-weight: 600;
      margin: 0 0 20px 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    /* Progress summary */
    .progress-summary {
      display: flex;
      gap: 8px;
      margin-top: 15px;
    }
    
    .progress-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ddd;
    }
    
    .progress-dot.done {
      background: #28a745;
    }
    
    .progress-dot.current {
      background: var(--color-primary, #4a6cf7);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }
    
    /* Test analysis styles */
    .section-bars {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .section-bar {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .section-bar .label {
      min-width: 120px;
      font-weight: 500;
    }
    
    .section-bar .bar-wrap {
      flex: 1;
    }
    
    .progress-bar {
      height: 8px;
      background: #f0f0f0;
      border-radius: 4px;
      overflow: hidden;
    }
    
    .progress-bar-fill {
      height: 100%;
      background: var(--color-primary, #4a6cf7);
      border-radius: 4px;
      transition: width 0.5s ease;
    }
    
    .overall-score-wrap {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid var(--color-border, #e0e0e0);
    }
    
    /* Growth list */
    .growth-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .growth-list li {
      display: flex;
      align-items: flex-start;
      padding: 15px 0;
      border-bottom: 1px solid var(--color-border, #e0e0e0);
    }
    
    .growth-list li:last-child {
      border-bottom: none;
    }
    
    .growth-list .num {
      width: 28px;
      height: 28px;
      background: var(--color-primary-light, #e8ebff);
      color: var(--color-primary, #4a6cf7);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 14px;
      margin-right: 15px;
      flex-shrink: 0;
    }
    
    .growth-list a {
      color: var(--color-primary, #4a6cf7);
      text-decoration: none;
      font-weight: 500;
    }
    
    .growth-list a:hover {
      text-decoration: underline;
    }
    
    /* Top career pill */
    .top-career-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 20px;
      background: var(--color-surface, #fff);
      border: 1px solid var(--color-border, #e0e0e0);
      border-radius: 50px;
      margin-top: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        display: none !important;
      }
      
      .main-content {
        margin-left: 0;
        max-width: 100%;
        padding: 20px;
      }
      
      .dashboard-cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <!-- Fixed Header -->
  <header class="site-header" style="position: fixed; top: 0; width: 100%; z-index: 1000; background: var(--color-surface, #fff);">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php" class="active">Dashboard</a>
          <a href="profile-setup.php">Profile</a>
          <a href="assessment.php">Assessment</a>
          <a href="career-details.php">Careers</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="profile-setup.php" class="btn btn-ghost">Profile</a>
        <a href="./logout.php" class="btn btn-ghost">Logout</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  
  <!-- Mobile Navigation -->
  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="profile-setup.php">Profile</a>
    <a href="assessment.php">Take Assessment</a>
    <a href="career-details.php">Careers</a>
    <a href="./logout.php">Logout</a>
  </nav>

  <!-- Main Dashboard Layout -->
  <div class="dashboard-container">
    <!-- Fixed Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">Menu</div>
      <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="profile-setup.php">Profile</a></li>
        <li><a href="assessment.php">Take Assessment</a></li>
        <li><a href="career-details.php">Careers</a></li>
        <li><a href="./logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p>Track your progress, view test analysis, and plan your career growth.</p>
      </div>

      <!-- Welcome & progress overview -->
      <div class="card welcome-card">
        <h2 id="welcomeTitle">
          <?php
          if ($profile_complete && $test_complete && $rec_count > 0) {
              echo "You're all set";
          } elseif ($profile_complete && $test_complete) {
              echo "Assessment complete";
          } elseif ($profile_complete) {
              echo "Profile complete";
          } else {
              echo "Welcome to CareerGuide";
          }
          ?>
        </h2>
        <p id="welcomeText">
          <?php
          if ($profile_complete && $test_complete && $rec_count > 0) {
              echo "Your profile and assessment are complete. Review your top career matches and follow the learning roadmaps to grow.";
          } elseif ($profile_complete && $test_complete) {
              echo "View your career recommendations and match percentages. Pick a career to see salary, skills, and a step-by-step learning path.";
          } elseif ($profile_complete) {
              echo "Take the career assessment next. It will unlock your personalized career recommendations.";
          } else {
              echo "Complete your profile (education, stream, skills, interests) first. Then take the assessment to get personalized career recommendations.";
          }
          ?>
        </p>
        <div class="progress-summary">
          <span class="progress-dot <?php echo $profile_complete ? 'done' : 'current'; ?>" title="Profile"></span>
          <span class="progress-dot <?php echo $test_complete ? 'done' : ($profile_complete ? 'current' : ''); ?>" title="Assessment"></span>
          <span class="progress-dot <?php echo $rec_count > 0 ? 'done' : ($test_complete ? 'current' : ''); ?>" title="Recommendations"></span>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="dashboard-cards">
        <div class="card stat-card">
          <div class="icon">📋</div>
          <div class="value"><?php echo $profile_complete ? 'Complete' : 'Incomplete'; ?></div>
          <div class="label">Profile</div>
          <a href="profile-setup.php"><?php echo $profile_complete ? 'View profile →' : 'Complete profile →'; ?></a>
        </div>
        
        <div class="card stat-card">
          <div class="icon">📝</div>
          <div class="value"><?php echo $test_complete ? 'Completed' : 'Not taken'; ?></div>
          <div class="label">Assessment</div>
          <a href="assessment.php"><?php echo $test_complete ? 'View results →' : 'Start assessment →'; ?></a>
        </div>
        
        <div class="card stat-card">
          <div class="icon">🎯</div>
          <div class="value"><?php echo $rec_count; ?></div>
          <div class="label">Career Recommendations</div>
          <a href="career-details.php"><?php echo $rec_count > 0 ? 'View recommendations →' : 'Get recommendations →'; ?></a>
        </div>
      </div>

      <!-- Test analysis -->
      <div class="card analysis-card">
        <h2 class="section-title">📊 Test Analysis</h2>
        <?php if (!$test_complete): ?>
          <div class="no-data">
            <p>You haven't taken the assessment yet. Complete the test to see your analysis.</p>
            <a href="assessment.php" class="btn btn-primary">Take Assessment</a>
          </div>
        <?php else: ?>
          <div class="section-bars">
            <?php if (isset($test_results['scores'])): ?>
              <?php
              $total_score = 0;
              $count = 0;
              foreach ($test_results['scores'] as $career => $score) {
                  $total_score += $score;
                  $count++;
              }
              $overall_score = $count > 0 ? round($total_score / $count) : 0;
              ?>
              <div class="section-bar">
                <span class="label">Overall Match</span>
                <div class="bar-wrap">
                  <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo $overall_score; ?>%"></div>
                  </div>
                </div>
                <span class="pct"><?php echo $overall_score; ?>%</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="overall-score-wrap">
            <div>
              <span style="font-size: 14px; color: #666;">Assessment taken</span>
              <div class="score"><?php echo isset($test_data['completed_at']) ? date('d M Y', strtotime($test_data['completed_at'])) : 'Recently'; ?></div>
            </div>
            <a href="test-results.php" class="btn btn-secondary">View Full Result</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Career growth -->
      <div class="card growth-card">
        <h2 class="section-title">📈 Career Growth</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Your next steps and tips to grow in your recommended careers.</p>
        
        <ul class="growth-list">
          <li>
            <span class="num">1</span>
            <span><strong>Complete your profile</strong> — Add education, stream, skills, and interests so we can match you better. 
            <a href="profile-setup.php"><?php echo $profile_complete ? 'Update profile' : 'Complete profile'; ?></a></span>
          </li>
          <li>
            <span class="num">2</span>
            <span><strong>Take the assessment</strong> — Answer questions on aptitude, interest, and skills. Takes about 10–15 minutes. 
            <a href="assessment.php"><?php echo $test_complete ? 'Retake test' : 'Start test'; ?></a></span>
          </li>
          <li>
            <span class="num">3</span>
            <span><strong>Review career options</strong> — See your match % and read about salary, skills, and roadmaps. 
            <a href="career-details.php">Browse careers</a></span>
          </li>
          <li>
            <span class="num">4</span>
            <span><strong>Follow a learning roadmap</strong> — Pick one career and follow the step-by-step path, courses, and certifications. 
            <a href="career-details.php">Choose a career</a></span>
          </li>
          <li>
            <span class="num">5</span>
            <span><strong>Build skills and apply</strong> — Learn the required skills, build small projects, and apply for internships or jobs.</span>
          </li>
        </ul>
        
        <?php if ($rec_count > 0 && !empty($top_career_name)): ?>
          <div style="margin-top: 25px;">
            <span style="font-size: 14px; color: #666;">Your top match:</span>
            <div class="top-career-pill">
              <span><?php echo htmlspecialchars($top_career_name); ?></span>
              <a href="career-details.php">View →</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <script>
    (function() {
      // Theme toggle
      var theme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', theme);
      var btn = document.getElementById('themeToggle');
      var sun = btn && btn.querySelector('.icon-sun');
      var moon = btn && btn.querySelector('.icon-moon');
      if (btn) {
        if (theme === 'dark') { 
          if (sun) sun.classList.add('sr-only'); 
          if (moon) moon.classList.remove('sr-only'); 
        } else { 
          if (sun) sun.classList.remove('sr-only'); 
          if (moon) moon.classList.add('sr-only'); 
        }
        btn.addEventListener('click', function() {
          var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
          document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
          localStorage.setItem('theme', isDark ? 'light' : 'dark');
          if (sun) sun.classList.toggle('sr-only', !isDark);
          if (moon) moon.classList.toggle('sr-only', isDark);
        });
      }

      // Mobile menu toggle
      var hamburger = document.getElementById('hamburger');
      var mobileNav = document.getElementById('mobileNav');
      if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() { 
          hamburger.classList.toggle('open'); 
          mobileNav.classList.toggle('open'); 
        });
        mobileNav.querySelectorAll('a').forEach(function(a) { 
          a.addEventListener('click', function() { 
            hamburger.classList.remove('open'); 
            mobileNav.classList.remove('open'); 
          }); 
        });
      }

      // Animate progress bars on load
      document.addEventListener('DOMContentLoaded', function() {
        var progressBars = document.querySelectorAll('.progress-bar-fill');
        progressBars.forEach(function(bar) {
          var width = bar.style.width;
          bar.style.width = '0';
          setTimeout(function() {
            bar.style.width = width;
          }, 300);
        });
      });
      
      // Show sidebar on desktop, hide on mobile
      function checkSidebar() {
        var sidebar = document.getElementById('sidebar');
        if (window.innerWidth >= 768) {
          sidebar.style.display = 'block';
        } else {
          sidebar.style.display = 'none';
        }
      }
      
      window.addEventListener('resize', checkSidebar);
      checkSidebar(); // Initial check
    })();
  </script>
</body>
</html>