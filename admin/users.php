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

// Handle form submissions
$message = '';
$message_type = '';

// Handle user deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Don't allow deleting self
    if ($delete_id == $admin_id) {
        $message = 'You cannot delete your own account';
        $message_type = 'error';
    } else {
        // Start transaction to delete related data
        mysqli_begin_transaction($conn);
        
        try {
            // Delete from related tables first (to maintain referential integrity)
            $tables = ['user_test_responses', 'user_tests', 'user_skills', 'user_careers', 'user_profiles'];
            foreach ($tables as $table) {
                mysqli_query($conn, "DELETE FROM $table WHERE user_id = $delete_id");
            }
            
            // Finally delete from users table
            $sql = "DELETE FROM users WHERE id = $delete_id";
            if (mysqli_query($conn, $sql)) {
                mysqli_commit($conn);
                header("Location: users.php?success=deleted");
                exit();
            } else {
                mysqli_rollback($conn);
                $message = 'Error deleting user: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = 'Error deleting user: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle admin toggle
if (isset($_GET['toggle_admin'])) {
    $toggle_id = intval($_GET['toggle_admin']);
    
    // Don't allow removing admin from self
    if ($toggle_id == $admin_id && isset($_GET['action']) && $_GET['action'] == 'remove') {
        $message = 'You cannot remove admin rights from yourself';
        $message_type = 'error';
    } else {
        $action = isset($_GET['action']) && $_GET['action'] == 'remove' ? 0 : 1;
        $sql = "UPDATE users SET is_admin = $action WHERE id = $toggle_id";
        if (mysqli_query($conn, $sql)) {
            $msg = $action ? 'Admin rights granted' : 'Admin rights removed';
            header("Location: users.php?success=$msg");
            exit();
        } else {
            $message = 'Error updating user: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Handle success messages from redirect
if (isset($_GET['success'])) {
    $msg = $_GET['success'];
    if ($msg == 'deleted') {
        $message = 'User deleted successfully';
        $message_type = 'success';
    } elseif ($msg == 'Admin rights granted') {
        $message = 'Admin rights granted successfully';
        $message_type = 'success';
    } elseif ($msg == 'Admin rights removed') {
        $message = 'Admin rights removed successfully';
        $message_type = 'success';
    }
}

// Get all users with their test data
$sql = "SELECT 
            u.*,
            COUNT(ut.id) as test_count,
            up.education,
            up.stream,
            up.career_goal,
            up.skills,
            up.interests
        FROM users u
        LEFT JOIN user_tests ut ON u.id = ut.user_id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode JSON fields
        $row['skills'] = json_decode($row['skills'] ?? '[]', true) ?: [];
        $row['interests'] = json_decode($row['interests'] ?? '[]', true) ?: [];
        
        // Get test results for this user
        $user_id = $row['id'];
        $test_query = "SELECT results, completed_at FROM user_tests WHERE user_id = $user_id ORDER BY completed_at DESC LIMIT 1";
        $test_result = mysqli_query($conn, $test_query);
        $row['top_career'] = 'N/A';
        $row['top_score'] = 'N/A';
        $row['last_test_date'] = null;
        
        if ($test_result && mysqli_num_rows($test_result) > 0) {
            $test_row = mysqli_fetch_assoc($test_result);
            $row['last_test_date'] = $test_row['completed_at'];
            
            // Decode the results JSON
            $test_results = json_decode($test_row['results'], true);
            if (isset($test_results['scores']) && is_array($test_results['scores'])) {
                // Find the career with highest score
                arsort($test_results['scores']);
                $top_career_slug = key($test_results['scores']);
                $top_score = current($test_results['scores']);
                
                // Get career title from careers table
                $career_query = "SELECT title FROM careers WHERE slug = '$top_career_slug'";
                $career_result = mysqli_query($conn, $career_query);
                if ($career_result && mysqli_num_rows($career_result) > 0) {
                    $career_row = mysqli_fetch_assoc($career_result);
                    $row['top_career'] = $career_row['title'];
                    $row['top_score'] = $top_score;
                }
            }
        }
        
        $users[] = $row;
    }
}

// Count total users
$total_users = count($users);
$total_tests = 0;
foreach ($users as $user) {
    if ($user['test_count'] > 0) {
        $total_tests++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users & Results | CareerGuide Admin</title>
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
      margin: 0;
    }
    .admin-main { 
      padding: var(--space-lg); 
      max-width: 1400px;
      margin: 0 auto;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: var(--space-md);
      margin-bottom: var(--space-xl);
    }
    .stat-card {
      background: var(--color-bg);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      padding: var(--space-lg);
      display: flex;
      flex-direction: column;
    }
    .stat-card h3 {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      margin-bottom: var(--space-sm);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .stat-card .value {
      font-size: var(--text-2xl);
      font-weight: 700;
      color: var(--color-text-primary);
      margin-bottom: var(--space-xs);
    }
    .stat-card .description {
      font-size: var(--text-sm);
      color: var(--color-text-muted);
    }
    .user-cards { 
      display: grid; 
      gap: var(--space-md); 
      margin-top: var(--space-lg);
    }
    .user-card { 
      padding: var(--space-lg); 
      border: 1px solid var(--color-border); 
      border-radius: var(--radius-lg); 
      background: var(--color-bg);
      transition: all 0.2s ease;
    }
    .user-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    .user-card h3 { 
      font-size: var(--text-base); 
      margin-bottom: var(--space-xs); 
      color: var(--color-text-primary);
    }
    .user-card .meta { 
      font-size: var(--text-sm); 
      color: var(--color-text-muted); 
      margin-bottom: var(--space-sm);
      display: flex;
      flex-wrap: wrap;
      gap: var(--space-md);
    }
    .user-card .result { 
      font-size: var(--text-sm); 
      color: var(--color-text-secondary);
      margin-bottom: var(--space-sm);
    }
    .user-actions { 
      margin-top: var(--space-md); 
      display: flex; 
      gap: var(--space-sm); 
      flex-wrap: wrap;
    }
    .badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: var(--text-xs);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .skill-tag {
      display: inline-block;
      padding: 2px 8px;
      background: var(--color-bg-light);
      color: var(--color-text-secondary);
      border-radius: 12px;
      font-size: var(--text-xs);
      margin-right: 4px;
      margin-bottom: 4px;
    }
    .badge-admin {
      background: var(--color-primary-light);
      color: var(--color-primary-dark);
    }
    .badge-user {
      background: var(--color-bg-light);
      color: var(--color-text-secondary);
    }
    .badge-test {
      background: #d4edda;
      color: #155724;
    }
    .badge-no-test {
      background: #f8d7da;
      color: #721c24;
    }
    .badge-score {
      background: #e3f2fd;
      color: #1565c0;
      font-weight: 700;
    }
    .badge-info {
      background: #fff3cd;
      color: #856404;
    }
    .badge-education {
      background: #e8f5e9;
      color: #2e7d32;
    }
    .badge-stream {
      background: #f3e5f5;
      color: #7b1fa2;
    }
    .badge-goal {
      background: #e3f2fd;
      color: #1565c0;
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
    .table-wrap { 
      display: none; 
      overflow-x: auto;
      margin-top: var(--space-lg);
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      background: var(--color-bg);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }
    th, td { 
      padding: var(--space-md); 
      text-align: left; 
      border-bottom: 1px solid var(--color-border); 
    }
    th { 
      font-weight: 600; 
      color: var(--color-text-secondary); 
      background: var(--color-bg-light);
      border-bottom: 2px solid var(--color-border);
    }
    tr:hover {
      background: var(--color-bg-light);
    }
    .message {
      padding: var(--space-md);
      border-radius: var(--radius-md);
      margin-bottom: var(--space-lg);
      animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .message.success {
      background: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }
    .message.error {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }
    .empty-state {
      text-align: center;
      padding: var(--space-2xl);
      color: var(--color-text-secondary);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      margin-top: var(--space-lg);
    }
    @media (min-width: 1024px) {
      .table-wrap { display: block; }
      .user-cards { display: none; }
    }
    @media (max-width: 1023px) {
      .table-wrap { display: none; }
      .user-cards { display: grid; }
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 90%; max-width: 600px; height: 240px; border-radius: var(--radius-xl);"></div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span> Admin</a>
        <nav class="nav-links">
          <a href="index.php">Dashboard</a>
          <a href="careers.php">Careers</a>
          <a href="questions.php">Questions</a>
          <a href="users.php" class="active">Users</a>
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
        <a href="../index.php" target="_blank" class="btn btn-ghost">View Site</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
  
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php">Dashboard</a>
    <a href="careers.php">Careers</a>
    <a href="questions.php">Questions</a>
    <a href="users.php" class="active">Users</a>
    <a href="../index.php">View Site</a>
    <a href="logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1>Users & Test Results</h1>
    <p class="text-muted" style="margin-top: var(--space-xs);">View registered users and their assessment results.</p>
  </div>

  <main class="admin-main">
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value"><?php echo $total_users; ?></div>
        <div class="description">Registered users in the system</div>
      </div>
      <div class="stat-card">
        <h3>Tests Taken</h3>
        <div class="value"><?php echo $total_tests; ?></div>
        <div class="description">Users who completed assessments</div>
      </div>
      <div class="stat-card">
        <h3>Completion Rate</h3>
        <div class="value"><?php echo $total_users > 0 ? round(($total_tests / $total_users) * 100, 1) : 0; ?>%</div>
        <div class="description">Percentage of users who took tests</div>
      </div>
      <div class="stat-card">
        <h3>Admin Users</h3>
        <?php 
        $admin_count = 0;
        foreach ($users as $user) {
            if ($user['is_admin'] == 1) {
                $admin_count++;
            }
        }
        ?>
        <div class="value"><?php echo $admin_count; ?></div>
        <div class="description">Users with admin privileges</div>
      </div>
    </div>

    <?php if (empty($users)): ?>
      <div class="empty-state">
        <h3>No users found</h3>
        <p>No users have registered yet.</p>
      </div>
    <?php else: ?>
      <!-- Mobile Cards View -->
      <div class="user-cards" id="userCards">
        <?php foreach ($users as $user): ?>
          <div class="user-card">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            
            <div class="meta">
              <span><?php echo htmlspecialchars($user['email']); ?></span>
              <span>Joined: <?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
            </div>
            
            <?php if ($user['education'] || $user['stream'] || $user['career_goal']): ?>
              <div class="result">
                <?php if ($user['education']): ?>
                  <span class="badge badge-education"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['education']))); ?></span>
                <?php endif; ?>
                <?php if ($user['stream']): ?>
                  <span class="badge badge-stream"><?php echo htmlspecialchars(strtoupper($user['stream'])); ?></span>
                <?php endif; ?>
                <?php if ($user['career_goal']): ?>
                  <span class="badge badge-goal"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['career_goal']))); ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($user['skills'])): ?>
              <div class="result">
                <strong>Skills:</strong> 
                <?php foreach (array_slice($user['skills'], 0, 3) as $skill): ?>
                  <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                <?php endforeach; ?>
                <?php if (count($user['skills']) > 3): ?>
                  <span class="skill-tag">+<?php echo count($user['skills']) - 3; ?> more</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <div class="result">
              <strong>Tests Taken:</strong> 
              <?php if ($user['test_count'] > 0): ?>
                <span class="badge badge-test"><?php echo $user['test_count']; ?> test(s)</span>
                <?php if ($user['last_test_date']): ?>
                  <span class="badge badge-info">Last: <?php echo date('d M Y', strtotime($user['last_test_date'])); ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge-no-test">No tests</span>
              <?php endif; ?>
            </div>
            
            <?php if ($user['test_count'] > 0): ?>
              <div class="result">
                <strong>Top Career:</strong> <?php echo htmlspecialchars($user['top_career']); ?>
              </div>
              <div class="result">
                <strong>Top Score:</strong> <span class="badge badge-score"><?php echo $user['top_score']; ?></span>
              </div>
            <?php endif; ?>
            
            <div style="margin-top: var(--space-sm);">
              <?php if ($user['is_admin'] == 1): ?>
                <span class="badge badge-admin">Admin</span>
              <?php else: ?>
                <span class="badge badge-user">User</span>
              <?php endif; ?>
            </div>
            
            <div class="user-actions">
              <?php if ($user['id'] != $admin_id): ?>
                <?php if ($user['is_admin'] == 1): ?>
                  <a href="?toggle_admin=<?php echo $user['id']; ?>&action=remove" 
                     class="btn btn-secondary btn-sm"
                     onclick="return confirm('Remove admin rights from this user?')">
                    Remove Admin
                  </a>
                <?php else: ?>
                  <a href="?toggle_admin=<?php echo $user['id']; ?>" 
                     class="btn btn-secondary btn-sm"
                     onclick="return confirm('Grant admin rights to this user?')">
                    Make Admin
                  </a>
                <?php endif; ?>
                
                <a href="?delete=<?php echo $user['id']; ?>" 
                   class="btn btn-ghost btn-sm" 
                   onclick="return confirm('Are you sure you want to delete this user? This will also delete their test results and profile data.')">
                  Delete
                </a>
              <?php else: ?>
                <span style="color: var(--color-text-muted); font-size: var(--text-sm);">(Your account)</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Desktop Table View -->
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Email</th>
              <th>Education</th>
              <th>Stream</th>
              <th>Career Goal</th>
              <th>Status</th>
              <th>Tests Taken</th>
              <th>Last Test</th>
              <th>Top Career</th>
              <th>Top Score</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="userTableBody">
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?php echo $user['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                  <?php if ($user['education']): ?>
                    <span class="badge badge-education"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['education']))); ?></span>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($user['stream']): ?>
                    <span class="badge badge-stream"><?php echo htmlspecialchars(strtoupper($user['stream'])); ?></span>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($user['career_goal']): ?>
                    <span class="badge badge-goal"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['career_goal']))); ?></span>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($user['is_admin'] == 1): ?>
                    <span class="badge badge-admin">Admin</span>
                  <?php else: ?>
                    <span class="badge badge-user">User</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($user['test_count'] > 0): ?>
                    <span class="badge badge-test"><?php echo $user['test_count']; ?></span>
                  <?php else: ?>
                    <span class="badge badge-no-test">0</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($user['last_test_date']): ?>
                    <?php echo date('d M Y', strtotime($user['last_test_date'])); ?>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($user['top_career']); ?></td>
                <td>
                  <?php if ($user['top_score'] != 'N/A'): ?>
                    <span class="badge badge-score"><?php echo $user['top_score']; ?></span>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                <td>
                  <?php if ($user['id'] != $admin_id): ?>
                    <?php if ($user['is_admin'] == 1): ?>
                      <a href="?toggle_admin=<?php echo $user['id']; ?>&action=remove" 
                         class="btn btn-secondary btn-sm"
                         onclick="return confirm('Remove admin rights from this user?')">
                        Remove Admin
                      </a>
                    <?php else: ?>
                      <a href="?toggle_admin=<?php echo $user['id']; ?>" 
                         class="btn btn-secondary btn-sm"
                         onclick="return confirm('Grant admin rights to this user?')">
                        Make Admin
                      </a>
                    <?php endif; ?>
                    
                    <a href="?delete=<?php echo $user['id']; ?>" 
                       class="btn btn-ghost btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this user? This will also delete their test results and profile data.')">
                      Delete
                    </a>
                  <?php else: ?>
                    <span style="color: var(--color-text-muted); font-size: var(--text-sm);">Current user</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <script>
    // Theme toggle
    document.addEventListener('DOMContentLoaded', function() {
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
    });
  </script>
</body>
</html>