<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];

// Get stats from database
$stats = [];

// Total users
$sql = "SELECT COUNT(*) as total FROM users";
$result = mysqli_query($conn, $sql);
$stats['total_users'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Total careers
$sql = "SELECT COUNT(*) as total FROM careers";
$result = mysqli_query($conn, $sql);
$stats['total_careers'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Total tests taken
$sql = "SELECT COUNT(*) as total FROM user_tests WHERE test_type = 'career_assessment'";
$result = mysqli_query($conn, $sql);
$stats['total_tests'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Most recommended career
$sql = "SELECT c.title, COUNT(ut.id) as recommendation_count 
        FROM careers c 
        LEFT JOIN user_tests ut ON JSON_EXTRACT(ut.results, '$.suggested_careers') LIKE CONCAT('%\"', c.slug, '\"%') 
        GROUP BY c.id 
        ORDER BY recommendation_count DESC 
        LIMIT 1";
$result = mysqli_query($conn, $sql);
$top_career = $result && mysqli_num_rows($result) > 0 ? mysqli_fetch_assoc($result)['title'] : 'None yet';

// Recent users (last 7 days)
$sql = "SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result = mysqli_query($conn, $sql);
$stats['recent_users'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;

// Recent tests (last 7 days)
$sql = "SELECT COUNT(*) as total FROM user_tests WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result = mysqli_query($conn, $sql);
$stats['recent_tests'] = $result ? mysqli_fetch_assoc($result)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | CareerGuide</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .admin-header { 
      padding: var(--space-lg); 
      border-bottom: 1px solid var(--color-border); 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      flex-wrap: wrap; 
      gap: var(--space-md); 
      background: var(--color-bg);
    }
    .admin-header h1 { 
      font-size: var(--text-xl); 
      color: var(--color-text-primary);
    }
    .admin-main { 
      padding: var(--space-lg); 
      max-width: 1200px;
      margin: 0 auto;
    }
    .stats-grid { 
      display: grid; 
      gap: var(--space-lg); 
      grid-template-columns: 1fr; 
      margin-bottom: var(--space-2xl);
    }
    @media (min-width: 768px) { 
      .stats-grid { 
        grid-template-columns: repeat(2, 1fr); 
      } 
    }
    @media (min-width: 1024px) { 
      .stats-grid { 
        grid-template-columns: repeat(4, 1fr); 
      } 
    }
    .stat-card { 
      padding: var(--space-xl); 
      background: var(--color-bg);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      transition: transform 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    .stat-card .value { 
      font-size: var(--text-2xl); 
      font-weight: 700; 
      color: var(--color-primary); 
      margin-bottom: var(--space-xs); 
    }
    .stat-card .label { 
      font-size: var(--text-sm); 
      color: var(--color-text-secondary); 
    }
    .stat-card a { 
      display: block; 
      margin-top: var(--space-md); 
      font-size: var(--text-sm); 
      font-weight: 500;
      color: var(--color-primary);
      text-decoration: none;
    }
    .stat-card a:hover {
      text-decoration: underline;
    }
    .page-loader { 
      position: fixed; 
      inset: 0; 
      background: var(--color-bg); 
      z-index: 9999; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      transition: opacity 0.4s ease, visibility 0.4s ease; 
    }
    .page-loader.hidden { 
      opacity: 0; 
      visibility: hidden; 
    }
    .recent-activity {
      margin-top: var(--space-2xl);
    }
    .activity-card {
      background: var(--color-bg);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      padding: var(--space-xl);
      margin-top: var(--space-lg);
    }
    .admin-welcome {
      background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
      color: white;
      padding: var(--space-xl);
      border-radius: var(--radius-lg);
      margin-bottom: var(--space-xl);
    }
    .admin-welcome h2 {
      margin-bottom: var(--space-sm);
    }
    .admin-welcome p {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div style="display: flex; flex-wrap: wrap; gap: var(--space-md); padding: var(--space-lg);">
      <div class="skeleton skeleton-card" style="width: 200px; height: 120px; border-radius: var(--radius-xl);"></div>
      <div class="skeleton skeleton-card" style="width: 200px; height: 120px; border-radius: var(--radius-xl);"></div>
      <div class="skeleton skeleton-card" style="width: 200px; height: 120px; border-radius: var(--radius-xl);"></div>
      <div class="skeleton skeleton-card" style="width: 200px; height: 120px; border-radius: var(--radius-xl);"></div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span> Admin</a>
        <nav class="nav-links">
          <a href="index.php" class="active">Dashboard</a>
          <a href="careers.php">Careers</a>
          <a href="questions.php">Questions</a>
          <a href="users.php">Users</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <span style="color: var(--color-text-secondary); margin-right: var(--space-sm);">
          Hi, <?php echo htmlspecialchars($admin_name); ?>
        </span>
        <a href="../index.php" class="btn btn-ghost">View Site</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
  
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php" class="active">Dashboard</a>
    <a href="careers.php">Careers</a>
    <a href="questions.php">Questions</a>
    <a href="users.php">Users</a>
    <a href="../index.php">View Site</a>
    <a href="logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1>Dashboard Overview</h1>
    <div style="font-size: var(--text-sm); color: var(--color-text-secondary);">
      Last updated: <?php echo date('d M Y, h:i A'); ?>
    </div>
  </div>

  <main class="admin-main">
    <div class="admin-welcome">
      <h2>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h2>
      <p>Here's what's happening with your CareerGuide platform today.</p>
    </div>

    <div class="stats-grid">
      <div class="card stat-card">
        <div class="value"><?php echo $stats['total_users']; ?></div>
        <div class="label">Total Users</div>
        <a href="users.php">View all users →</a>
      </div>
      
      <div class="card stat-card">
        <div class="value"><?php echo $stats['total_careers']; ?></div>
        <div class="label">Career Options</div>
        <a href="careers.php">Manage careers →</a>
      </div>
      
      <div class="card stat-card">
        <div class="value"><?php echo $stats['total_tests']; ?></div>
        <div class="label">Tests Taken</div>
        <a href="users.php">View results →</a>
      </div>
      
      <div class="card stat-card">
        <div class="value"><?php echo htmlspecialchars($top_career); ?></div>
        <div class="label">Most Recommended Career</div>
        <a href="careers.php">Manage →</a>
      </div>
    </div>

    <div class="recent-activity">
      <h2 style="margin-bottom: var(--space-lg); color: var(--color-text-primary);">Recent Activity</h2>
      
      <div class="activity-card">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-xl);">
          <div>
            <h3 style="margin-bottom: var(--space-md); font-size: var(--text-base); color: var(--color-text-primary);">New Users (7 days)</h3>
            <div class="value"><?php echo $stats['recent_users']; ?></div>
            <div class="label">Registered recently</div>
          </div>
          
          <div>
            <h3 style="margin-bottom: var(--space-md); font-size: var(--text-base); color: var(--color-text-primary);">Recent Tests</h3>
            <div class="value"><?php echo $stats['recent_tests']; ?></div>
            <div class="label">Taken in last 7 days</div>
          </div>
          
          <div>
            <h3 style="margin-bottom: var(--space-md); font-size: var(--text-base); color: var(--color-text-primary);">Engagement Rate</h3>
            <div class="value">
              <?php 
                $engagement = $stats['total_users'] > 0 ? round(($stats['total_tests'] / $stats['total_users']) * 100) : 0;
                echo $engagement . '%';
              ?>
            </div>
            <div class="label">Users who took assessment</div>
          </div>
        </div>
      </div>
    </div>

    <div class="activity-card" style="margin-top: var(--space-xl);">
      <h3 style="margin-bottom: var(--space-lg); color: var(--color-text-primary);">Quick Actions</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md);">
        <a href="careers.php?action=add" class="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right: 8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add New Career
        </a>
        <a href="questions.php?action=add" class="btn btn-outline">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right: 8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Add Assessment Question
        </a>
        <a href="users.php" class="btn btn-outline">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right: 8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 2.5l-2.5 2.5m0 0l-2.5-2.5m2.5 2.5V14"/>
          </svg>
          View All Users
        </a>
        <a href="../index.php" target="_blank" class="btn btn-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right: 8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
          Preview Site
        </a>
      </div>
    </div>
  </main>

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

      // Mobile navigation
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

      // Hide loader
      setTimeout(function() { 
        document.getElementById('pageLoader').classList.add('hidden'); 
      }, 500);

      // Auto-refresh stats every 60 seconds
      setInterval(function() {
        // You could implement AJAX refresh here
        console.log('Stats updated at: ' + new Date().toLocaleTimeString());
      }, 60000);

    })();
  </script>
</body>
</html>