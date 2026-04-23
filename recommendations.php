<?php
require_once 'config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$pdo = getDBConnection();

// 1. Fetch latest test results
$test_complete = false;
$test_results = [];
$top_career_slug = '';
$top_career_data = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM user_tests WHERE user_id = :user_id ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $test_data = $stmt->fetch();
    
    if ($test_data) {
        $test_complete = true;
        if (!empty($test_data['results'])) {
            $test_results = json_decode($test_data['results'], true);
            if (isset($test_results['suggested_careers']) && !empty($test_results['suggested_careers'])) {
                $top_career_slug = $test_results['suggested_careers'][0];
                
                // Fetch top career details
                $careerStmt = $pdo->prepare("SELECT * FROM careers WHERE slug = :slug");
                $careerStmt->execute(['slug' => $top_career_slug]);
                $top_career_data = $careerStmt->fetch();
            }
        }
    }
} catch (PDOException $e) {
    error_log("Recommendation fetch error: " . $e->getMessage());
}

// Redirect if no test results found
if (!$test_complete || !$top_career_data) {
    header("Location: assessment.php");
    exit();
}

$career_id = $top_career_data['id'];

// 2. Fetch Online Courses (2 cards)
$online_courses = [];
try {
    // Assuming online courses might be marked or we just take first 2
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE career_id = :career_id AND (description LIKE '%online%' OR platform IN ('Coursera', 'Udemy', 'edX', 'freeCodeCamp', 'The Odin Project')) LIMIT 2");
    $stmt->execute(['career_id' => $career_id]);
    $online_courses = $stmt->fetchAll();
    
    // Fallback if none found with 'online' keyword
    if (count($online_courses) < 2) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE career_id = :career_id LIMIT 2");
        $stmt->execute(['career_id' => $career_id]);
        $online_courses = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Online courses fetch error: " . $e->getMessage());
}

// 3. Fetch Offline Courses / Training Centers (2 cards)
$offline_courses = [];
try {
    // Using colleges table for offline institutes if mode is offline
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE career_id = :career_id AND mode = 'offline' LIMIT 2");
    $stmt->execute(['career_id' => $career_id]);
    $offline_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Offline courses fetch error: " . $e->getMessage());
}

// 4. Fetch Colleges / Universities (2 cards)
$colleges = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE career_id = :career_id AND type = 'college' LIMIT 2");
    $stmt->execute(['career_id' => $career_id]);
    $colleges = $stmt->fetchAll();
    
    // If no colleges found, take any 2 from colleges table that aren't already in offline_courses
    if (count($colleges) < 2) {
        $offline_ids = array_column($offline_courses, 'id');
        $placeholders = empty($offline_ids) ? "" : "AND id NOT IN (" . implode(',', $offline_ids) . ")";
        $stmt = $pdo->prepare("SELECT * FROM colleges WHERE career_id = :career_id $placeholders LIMIT 2");
        $stmt->execute(['career_id' => $career_id]);
        $colleges = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Colleges fetch error: " . $e->getMessage());
}

// 5. Fetch Saved Resource IDs for this user (to show 'saved' state)
$saved_courses = [];
$saved_colleges = [];
try {
    $stmt = $pdo->prepare("SELECT resource_id FROM user_saved_resources WHERE user_id = :user_id AND resource_type = 'course'");
    $stmt->execute(['user_id' => $user_id]);
    $saved_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT resource_id FROM user_saved_resources WHERE user_id = :user_id AND resource_type = 'college'");
    $stmt->execute(['user_id' => $user_id]);
    $saved_colleges = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Saved resources fetch error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>References & Resources | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent-online: #3b82f6;
      --accent-offline: #10b981;
      --accent-college: #f59e0b;
      --color-primary: #4f46e5;
      --color-primary-dark: #4338ca;
    }

    .reference-page {
      padding: 40px 20px;
      max-width: 1100px;
      margin: 60px auto 0;
    }

    .section-header {
      margin-bottom: 40px;
      text-align: left;
    }

    .section-header h1 {
      font-size: clamp(24px, 5vw, 36px);
      font-weight: 800;
      color: var(--color-text-primary);
      margin-bottom: 10px;
    }

    .section-header p {
      font-size: 18px;
      color: var(--color-text-secondary);
    }

    /* Modern Hero Banner */
    .recommendation-hero {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      color: white;
      padding: 50px;
      border-radius: 24px;
      margin-bottom: 60px;
      box-shadow: 0 20px 40px rgba(79, 70, 229, 0.15);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .recommendation-hero::after {
      content: '🎯';
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 180px;
      opacity: 0.1;
      pointer-events: none;
    }

    .recommendation-hero h2 {
      font-size: clamp(20px, 4vw, 28px);
      font-weight: 700;
      margin: 0;
    }

    .recommendation-hero .career-title {
      background: rgba(255, 255, 255, 0.2);
      padding: 8px 20px;
      border-radius: 50px;
      font-size: 18px;
      font-weight: 600;
      width: fit-content;
      backdrop-filter: blur(10px);
    }

    .recommendation-hero p {
      font-size: 18px;
      line-height: 1.6;
      max-width: 700px;
      opacity: 0.95;
      margin: 0;
    }

    .hero-actions {
      display: flex;
      gap: 15px;
      margin-top: 10px;
    }

    .hero-btn {
      background: white;
      color: var(--color-primary);
      padding: 12px 24px;
      border-radius: 12px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .hero-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    }

    .resource-section {
      margin-bottom: 80px;
    }

    .resource-section h3 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 15px;
      color: var(--color-text-primary);
    }

    .resource-section h3::after {
      content: '';
      flex-grow: 1;
      height: 2px;
      background: var(--color-border);
      opacity: 0.5;
    }

    .resource-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      gap: 30px;
    }

    .resource-card {
      background: var(--color-surface, #fff);
      border-radius: 20px;
      border: 1px solid var(--color-border, #e2e8f0);
      padding: 30px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      position: relative;
    }

    .resource-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
      border-color: var(--color-primary);
    }

    .card-tag {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 15px;
      padding: 4px 12px;
      border-radius: 50px;
      width: fit-content;
    }

    .online-tag { background: #dbeafe; color: #1e40af; }
    .offline-tag { background: #d1fae5; color: #065f46; }
    .college-tag { background: #fef3c7; color: #92400e; }

    .resource-card h4 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--color-text-primary);
      line-height: 1.3;
    }

    .resource-card .meta {
      font-size: 14px;
      color: var(--color-text-secondary);
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
    }

    .resource-card .meta span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .resource-card .desc {
      font-size: 15px;
      line-height: 1.6;
      color: var(--color-text-secondary);
      margin-bottom: 25px;
      flex-grow: 1;
    }

    .cta-btn {
      width: 100%;
      text-align: center;
      padding: 14px;
      border-radius: 12px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s ease;
      display: inline-block;
    }

    .online-btn { background: var(--accent-online); color: white; }
    .online-btn:hover { background: #2563eb; }

    .offline-btn { background: var(--accent-offline); color: white; }
    .offline-btn:hover { background: #059669; }

    .college-btn { background: var(--accent-college); color: white; }
    .college-btn:hover { background: #d97706; }

    /* Modern Save Button */
    .save-btn {
      position: absolute;
      top: 25px;
      right: 25px;
      background: var(--color-bg-light);
      border: 1px solid var(--color-border);
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      z-index: 10;
      color: var(--color-text-secondary);
    }

    .save-btn:hover {
      background: #fee2e2;
      color: #ef4444;
      border-color: #fecaca;
      transform: scale(1.1);
    }

    .save-btn.saved {
      background: #ef4444;
      border-color: #ef4444;
      color: white;
    }

    .save-btn svg {
      width: 20px;
      height: 20px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2.5;
    }

    .save-btn.saved svg {
      fill: currentColor;
    }

    @media (max-width: 768px) {
      .recommendation-hero { padding: 30px; }
      .recommendation-hero::after { display: none; }
      .resource-grid { grid-template-columns: 1fr; }
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
          <a href="test-results.php">Results</a>
          <a href="recommendations.php" class="active">References</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="saved-resources.php" class="btn btn-ghost">Saved</a>
        <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  
  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php">Dashboard</a>
    <a href="test-results.php">Test Results</a>
    <a href="recommendations.php" class="active">References</a>
    <a href="saved-resources.php">Saved Items</a>
    <a href="./logout.php">Logout</a>
  </nav>

  <main class="reference-page">
    <div class="section-header">
        <h1>Personalized References</h1>
        <p>Curated learning paths and top institutions for your career growth.</p>
    </div>

    <!-- Personalized Recommendation Hero -->
    <div class="recommendation-hero">
      <h2>Your Recommended Path</h2>
      <div class="career-title"><?php echo htmlspecialchars($top_career_data['title']); ?></div>
      <p>
        Your assessment shows a <strong><?php echo $test_results['scores'][$top_career_slug]; ?>% compatibility</strong> 
        with this field. We've hand-picked the best resources to help you bridge the gap and start your journey.
      </p>
      <div class="hero-actions">
          <a href="career-path.php?slug=<?php echo urlencode($top_career_slug); ?>" class="hero-btn">View Full Roadmap →</a>
      </div>
    </div>

    <!-- Online Courses Section -->
    <section class="resource-section">
      <h3>🌐 Online Learning Path</h3>
      <div class="resource-grid">
        <?php foreach ($online_courses as $course): ?>
          <?php $is_saved = in_array($course['id'], $saved_courses); ?>
          <div class="resource-card">
            <button class="save-btn <?php echo $is_saved ? 'saved' : ''; ?>" 
                    data-type="course" data-id="<?php echo $course['id']; ?>">
              <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
            </button>
            <span class="card-tag online-tag">Online Course</span>
            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
            <div class="meta">
              <span>🏫 <?php echo htmlspecialchars($course['platform']); ?></span>
              <?php if($course['duration']): ?>
                <span>⏱️ <?php echo htmlspecialchars($course['duration']); ?></span>
              <?php endif; ?>
            </div>
            <p class="desc"><?php echo htmlspecialchars(substr($course['description'] ?? 'Comprehensive online program.', 0, 140)); ?>...</p>
            <a href="<?php echo htmlspecialchars($course['url']); ?>" target="_blank" class="cta-btn online-btn">
              <?php echo $course['is_free'] ? 'Enroll Free' : 'Start Learning'; ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Offline Courses Section -->
    <section class="resource-section">
      <h3>🏢 Training Centers & Workshops</h3>
      <div class="resource-grid">
        <?php foreach ($offline_courses as $off): ?>
          <?php $is_saved = in_array($off['id'], $saved_colleges); ?>
          <div class="resource-card">
            <button class="save-btn <?php echo $is_saved ? 'saved' : ''; ?>" 
                    data-type="college" data-id="<?php echo $off['id']; ?>">
              <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
            </button>
            <span class="card-tag offline-tag"><?php echo ucwords(str_replace('_', ' ', $off['type'])); ?></span>
            <h4><?php echo htmlspecialchars($off['name']); ?></h4>
            <div class="meta">
              <span>📍 <?php echo htmlspecialchars($off['location']); ?></span>
            </div>
            <p class="desc"><?php echo htmlspecialchars(substr($off['description'] ?? 'Intensive in-person sessions to build practical skills.', 0, 140)); ?>...</p>
            <a href="<?php echo htmlspecialchars($off['website_url'] ?? '#'); ?>" target="_blank" class="cta-btn offline-btn">Register Now</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- College/University Section -->
    <section class="resource-section">
      <h3>🎓 Recommended Institutions</h3>
      <div class="resource-grid">
        <?php foreach ($colleges as $college): ?>
          <?php $is_saved = in_array($college['id'], $saved_colleges); ?>
          <div class="resource-card">
            <button class="save-btn <?php echo $is_saved ? 'saved' : ''; ?>" 
                    data-type="college" data-id="<?php echo $college['id']; ?>">
              <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
            </button>
            <span class="card-tag college-tag">University</span>
            <h4><?php echo htmlspecialchars($college['name']); ?></h4>
            <div class="meta">
              <span>📍 <?php echo htmlspecialchars($college['location']); ?></span>
            </div>
            <p class="desc"><strong>Program:</strong> <?php echo htmlspecialchars($college['course_offered']); ?><br>
            <?php echo htmlspecialchars(substr($college['description'] ?? 'Premier institution offering specialized programs.', 0, 100)); ?>...</p>
            <a href="<?php echo htmlspecialchars($college['website_url'] ?? '#'); ?>" target="_blank" class="cta-btn college-btn">View College</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div style="text-align: center; margin-top: 40px; padding-bottom: 60px;">
      <p style="color: var(--color-text-secondary); margin-bottom: 20px;">Want to see other career paths?</p>
      <a href="test-results.php" class="btn btn-outline">Back to All Results</a>
    </div>
  </main>

  <script>
    (function() {
      // Theme toggle
      var theme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', theme);
      var btn = document.getElementById('themeToggle');
      if (btn) {
        btn.addEventListener('click', function() {
          var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
          document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
          localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });
      }

      // Mobile menu
      var hamburger = document.getElementById('hamburger');
      var mobileNav = document.getElementById('mobileNav');
      if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() {
          hamburger.classList.toggle('open');
          mobileNav.classList.toggle('open');
        });
      }

      // Save resource functionality
      const csrfToken = '<?php echo $csrf_token; ?>';
      document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
          e.preventDefault();
          const type = this.dataset.type;
          const id = this.dataset.id;
          
          try {
            const formData = new FormData();
            formData.append('type', type);
            formData.append('id', id);
            formData.append('csrf', csrfToken);

            const response = await fetch('api/save-resource.php', {
              method: 'POST',
              body: formData
            });

            const result = await response.json();
            if (result.success) {
              this.classList.toggle('saved', result.saved);
            } else {
              alert(result.message || 'Failed to save resource');
            }
          } catch (error) {
            console.error('Error:', error);
          }
        });
      });
    })();
  </script>
</body>
</html>
