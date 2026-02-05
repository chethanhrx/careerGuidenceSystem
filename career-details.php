<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store current page to return after login
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get search query if any
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// Build query
$sql = "SELECT * FROM careers WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (title LIKE '%$search%' OR overview LIKE '%$search%' OR skills LIKE '%$search%')";
}

$sql .= " ORDER BY title ASC";

$result = mysqli_query($conn, $sql);

// Get user's test results for match percentages
$user_scores = [];
$test_sql = "SELECT results FROM user_tests WHERE user_id = '$user_id' AND test_type = 'career_assessment' ORDER BY completed_at DESC LIMIT 1";
$test_result = mysqli_query($conn, $test_sql);
if ($test_result && mysqli_num_rows($test_result) > 0) {
    $test_data = mysqli_fetch_assoc($test_result);
    if (!empty($test_data['results'])) {
        $results = json_decode($test_data['results'], true);
        if (is_array($results) && isset($results['scores'])) {
            $user_scores = $results['scores'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Recommendations | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .careers-page { padding: var(--space-lg); max-width: 1200px; margin: 0 auto; }
    .page-header { margin-bottom: var(--space-2xl); }
    .page-header h1 { margin-bottom: var(--space-sm); }
    .filters { 
      display: flex; 
      flex-wrap: wrap; 
      gap: var(--space-md); 
      margin-bottom: var(--space-xl);
      padding: var(--space-lg);
      background: var(--color-bg-light);
      border-radius: var(--radius-lg);
    }
    .search-box { flex: 1; min-width: 300px; }
    .careers-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
      gap: var(--space-lg); 
      margin-bottom: var(--space-2xl);
    }
    .career-card { 
      border: 1px solid var(--color-border); 
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      background: var(--color-card-bg, var(--color-bg));
    }
    .career-card:hover { 
      transform: translateY(-4px); 
      box-shadow: 0 8px 24px rgba(0,0,0,0.1); 
    }
    .career-card-header { 
      padding: var(--space-lg); 
      background: var(--color-bg-light);
      border-bottom: 1px solid var(--color-border);
    }
    .career-card-body { padding: var(--space-lg); }
    .career-card-footer { 
      padding: var(--space-md) var(--space-lg); 
      background: var(--color-bg-light);
      border-top: 1px solid var(--color-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .match-badge { 
      padding: 4px 12px; 
      background: var(--color-primary-light); 
      color: var(--color-primary); 
      border-radius: 20px; 
      font-size: var(--text-sm); 
      font-weight: 600;
    }
    .type-badge { 
      display: inline-block; 
      padding: 4px 12px; 
      background: var(--color-secondary-light); 
      color: var(--color-secondary); 
      border-radius: 20px; 
      font-size: var(--text-sm); 
      font-weight: 500;
      margin-bottom: var(--space-sm);
    }
    .empty-state { 
      text-align: center; 
      padding: var(--space-2xl); 
      color: var(--color-text-secondary);
    }
    .salary-range { 
      color: var(--color-primary); 
      font-weight: 600; 
      font-size: var(--text-sm);
      margin-top: var(--space-sm);
    }
    .skill-badge {
      font-size: var(--text-xs); 
      padding: 2px 8px; 
      background: var(--color-bg-light); 
      border-radius: 12px;
      color: var(--color-text-secondary);
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="profile-setup.php">Profile</a>
          <a href="assessment.php">Assessment</a>
          <a href="careers.php" class="active">Careers</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile-setup.php">Profile</a>
    <a href="assessment.php">Assessment</a>
    <a href="careers.php" class="active">Careers</a>
    <a href="./logout.php">Logout</a>
  </nav>

  <main class="careers-page">
    <div class="page-header">
      <h1>Career Recommendations</h1>
      <p class="text-muted">Browse and explore career options based on your assessment results.</p>
    </div>

    <div class="filters">
      <form method="get" action="careers.php" class="search-box">
        <div class="form-group">
          <input type="text" 
                 name="search" 
                 class="form-input" 
                 placeholder="Search careers (title, skills, description...)" 
                 value="<?php echo htmlspecialchars($search); ?>">
          <button type="submit" class="btn btn-primary" style="margin-top: var(--space-sm);">Search</button>
        </div>
      </form>
      
      <div>
        <a href="careers.php" class="btn btn-outline">Clear Search</a>
      </div>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <div class="careers-grid">
        <?php while ($career = mysqli_fetch_assoc($result)): 
          $match_percentage = $user_scores[$career['slug']] ?? 0;
          $skills = json_decode($career['skills'] ?? '[]', true) ?: [];
          $first_skills = array_slice($skills, 0, 3);
          
          // Determine career type based on slug or title
          $career_type = 'Technology'; // Default
          $slug = strtolower($career['slug'] ?? '');
          if (strpos($slug, 'data') !== false) $career_type = 'Data & Analytics';
          elseif (strpos($slug, 'design') !== false) $career_type = 'Design';
          elseif (strpos($slug, 'market') !== false) $career_type = 'Marketing';
          elseif (strpos($slug, 'manage') !== false) $career_type = 'Management';
        ?>
          <div class="career-card">
            <div class="career-card-header">
              <span class="type-badge"><?php echo $career_type; ?></span>
              <h3 style="margin: var(--space-xs) 0; color: var(--color-text-primary);"><?php echo htmlspecialchars($career['title']); ?></h3>
              <?php if ($match_percentage > 0): ?>
                <span class="match-badge"><?php echo $match_percentage; ?>% match</span>
              <?php endif; ?>
            </div>
            
            <div class="career-card-body">
              <p class="text-muted" style="margin-bottom: var(--space-md); line-height: 1.5; color: var(--color-text-secondary);">
                <?php 
                  $overview = $career['overview'] ?? '';
                  echo htmlspecialchars(strlen($overview) > 120 ? substr($overview, 0, 120) . '...' : $overview); 
                ?>
              </p>
              
              <?php if (!empty($first_skills)): ?>
                <div style="margin-bottom: var(--space-md);">
                  <strong style="font-size: var(--text-sm); color: var(--color-text-secondary); display: block; margin-bottom: var(--space-xs);">Key Skills:</strong>
                  <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    <?php foreach ($first_skills as $skill): ?>
                      <span class="skill-badge">
                        <?php echo htmlspecialchars($skill); ?>
                      </span>
                    <?php endforeach; ?>
                    <?php if (count($skills) > 3): ?>
                      <span style="font-size: var(--text-xs); padding: 2px 8px; color: var(--color-text-muted);">
                        +<?php echo count($skills) - 3; ?> more
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($career['salary_range'])): ?>
                <div class="salary-range">
                  <?php echo htmlspecialchars($career['salary_range']); ?>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="career-card-footer">
              <a href="career-details.php?slug=<?php echo urlencode($career['slug']); ?>" class="btn btn-primary btn-sm">
                View Details
              </a>
              <span style="font-size: var(--text-xs); color: var(--color-text-muted);">
                <?php 
                  $job_roles = json_decode($career['job_roles'] ?? '[]', true) ?: [];
                  echo !empty($job_roles) ? htmlspecialchars($job_roles[0]) : 'Explore Roles';
                ?>
              </span>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <h3>No careers found</h3>
        <p>
          <?php if (!empty($search)): ?>
            Try adjusting your search criteria or clear the search to see all careers.
          <?php else: ?>
            No career data available in the database.
          <?php endif; ?>
        </p>
        <?php if (!empty($search)): ?>
          <a href="careers.php" class="btn btn-primary" style="margin-top: var(--space-md);">
            View All Careers
          </a>
        <?php endif; ?>
        <a href="assessment.php" class="btn btn-outline" style="margin-top: var(--space-md); margin-left: var(--space-sm);">
          Take Assessment
        </a>
      </div>
    <?php endif; ?>
    
    <?php if (empty($search)): ?>
      <div class="card" style="margin-top: var(--space-xl); padding: var(--space-lg); background: var(--color-bg-light); border-color: var(--color-border);">
        <h3 style="margin-bottom: var(--space-md); color: var(--color-text-primary);">Need personalized recommendations?</h3>
        <p class="text-muted" style="margin-bottom: var(--space-md); color: var(--color-text-secondary);">
          Take our career assessment to get personalized career recommendations based on your skills, interests, and personality.
        </p>
        <div style="display: flex; gap: var(--space-md);">
          <a href="assessment.php" class="btn btn-primary">Take Career Assessment</a>
          <a href="dashboard.php" class="btn btn-outline">Go to Dashboard</a>
        </div>
      </div>
    <?php endif; ?>
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

      // Search form submission on enter
      document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          this.form.submit();
        }
      });

      // Auto-focus search input
      if (window.location.search.includes('search=')) {
        document.querySelector('input[name="search"]')?.focus();
      }

    })();
  </script>
</body>
</html>