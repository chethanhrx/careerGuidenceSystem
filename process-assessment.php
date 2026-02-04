<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Results | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .result-page { padding: var(--space-lg); max-width: 720px; margin: 0 auto; }
    .result-header { text-align: center; margin-bottom: var(--space-2xl); }
    .result-header h1 { margin-bottom: var(--space-sm); }
    .overall-score { font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary); margin: var(--space-md) 0; }
    .section-scores { margin-bottom: var(--space-xl); }
    .section-card { padding: var(--space-lg); margin-bottom: var(--space-md); }
    .section-card h3 { font-size: var(--text-base); margin-bottom: var(--space-sm); }
    .section-card .progress-bar { margin-top: var(--space-sm); height: 10px; }
    .insights { padding: var(--space-lg); margin-top: var(--space-xl); }
    .insights h2 { margin-bottom: var(--space-md)<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get latest test results
$sql = "SELECT * FROM user_tests WHERE user_id = '$user_id' ORDER BY completed_at DESC LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: assessment.php");
    exit();
}

$test_data = mysqli_fetch_assoc($result);
$test_results = json_decode($test_data['results'], true);

// Calculate overall score
$overall_score = 0;
if (isset($test_results['scores']) && count($test_results['scores']) > 0) {
    $total_score = 0;
    $count = 0;
    foreach ($test_results['scores'] as $career => $score) {
        $total_score += $score;
        $count++;
    }
    $overall_score = $count > 0 ? round($total_score / $count) : 0;
}

// Get career details for suggested careers
$suggested_careers = [];
if (isset($test_results['suggested_careers'])) {
    foreach ($test_results['suggested_careers'] as $career_slug) {
        $sql_career = "SELECT * FROM careers WHERE slug = '$career_slug'";
        $result_career = mysqli_query($conn, $sql_career);
        if ($result_career && mysqli_num_rows($result_career) > 0) {
            $career_data = mysqli_fetch_assoc($career_data);
            $career_data['score'] = $test_results['scores'][$career_slug] ?? 0;
            $suggested_careers[] = $career_data;
        }
    }
}

// Prepare insights based on scores
$insights = [];
if ($overall_score >= 80) {
    $insights[] = "Excellent! Your assessment shows strong alignment with multiple career paths.";
    $insights[] = "Your skills and interests are well-balanced for professional success.";
} elseif ($overall_score >= 60) {
    $insights[] = "Good match! You have solid foundations for several career options.";
    $insights[] = "Consider focusing on specific skills to increase your match percentage.";
} else {
    $insights[] = "Your assessment shows potential in various areas.";
    $insights[] = "Consider exploring different skill development paths.";
}

if (isset($test_results['scores'])) {
    $top_career = array_search(max($test_results['scores']), $test_results['scores']);
    if ($top_career) {
        $insights[] = "Your strongest match is with " . ucfirst(str_replace('-', ' ', $top_career)) . " careers.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Results | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .result-page { padding: var(--space-lg); max-width: 720px; margin: 0 auto; }
    .result-header { text-align: center; margin-bottom: var(--space-2xl); }
    .result-header h1 { margin-bottom: var(--space-sm); }
    .overall-score { font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary); margin: var(--space-md) 0; }
    .section-scores { margin-bottom: var(--space-xl); }
    .section-card { padding: var(--space-lg); margin-bottom: var(--space-md); }
    .section-card h3 { font-size: var(--text-base); margin-bottom: var(--space-sm); }
    .section-card .progress-bar { margin-top: var(--space-sm); height: 10px; }
    .insights { padding: var(--space-lg); margin-top: var(--space-xl); }
    .insights h2 { margin-bottom: var(--space-md); }
    .insight-item { padding: var(--space-sm) 0; border-bottom: 1px solid var(--color-border); font-size: var(--text-sm); color: var(--color-text-secondary); }
    .insight-item:last-child { border-bottom: none; }
    .cta-wrap { margin-top: var(--space-xl); text-align: center; }
    .cta-wrap .btn { min-width: 200px; }
    .page-loader { position: fixed; inset: 0; background: var(--color-bg); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, visibility 0.4s ease; }
    .page-loader.hidden { opacity: 0; visibility: hidden; }
    .skeleton-chart { height: 120px; border-radius: var(--radius-lg); margin-bottom: var(--space-md); }
    
    /* Career matches */
    .career-matches { margin-top: var(--space-xl); }
    .career-match-card { padding: var(--space-lg); margin-bottom: var(--space-md); }
    .career-match-card h3 { margin-bottom: var(--space-sm); }
    .match-score { font-size: var(--text-xl); font-weight: 700; color: var(--color-primary); }
    .match-label { font-size: var(--text-sm); color: var(--color-text-secondary); }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="container" style="padding: var(--space-xl); width: 100%; max-width: 400px;">
      <div class="skeleton skeleton-title" style="height: 32px; margin-bottom: var(--space-lg);"></div>
      <div class="skeleton skeleton-chart"></div>
      <div class="skeleton skeleton-chart"></div>
      <div class="skeleton skeleton-chart"></div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="profile-setup.php">Profile</a>
          <a href="assessment.php">Assessment</a>
          <a href="test-results.php" class="active">Test Result</a>
          <a href="careers.php">Careers</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="careers.php" class="btn btn-primary">View Recommendations</a>
        <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile-setup.php">Profile</a>
    <a href="assessment.php">Take Assessment</a>
    <a href="test-results.php" class="active">Test Result</a>
    <a href="careers.php">Careers</a>
    <a href="logout.php">Logout</a>
  </nav>

  <div class="main-with-sidebar">
    <aside class="sidebar" id="sidebar" style="display: none;">
      <div class="sidebar-brand">Menu</div>
      <ul class="sidebar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="profile-setup.php">Profile</a></li>
        <li><a href="assessment.php">Take Assessment</a></li>
        <li><a href="test-results.php" class="active">Test Result</a></li>
        <li><a href="careers.php">Careers</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </aside>
    
    <main class="result-page">
      <div class="result-header">
        <h1>Assessment Results</h1>
        <p class="text-muted">Your performance analysis</p>
        <div class="overall-score" id="overallScore"><?php echo $overall_score; ?>%</div>
        <p class="text-muted" id="overallLabel">Overall match score</p>
        <p class="text-muted" style="font-size: var(--text-sm);">
          Assessment taken: <?php echo date('d M Y, h:i A', strtotime($test_data['completed_at'])); ?>
        </p>
      </div>

      <?php if (!empty($suggested_careers)): ?>
        <div class="career-matches">
          <h2 style="margin-bottom: var(--space-lg);">Top Career Matches</h2>
          <?php foreach ($suggested_careers as $career): ?>
            <div class="card career-match-card">
              <h3><?php echo htmlspecialchars($career['title']); ?></h3>
              <div class="match-score"><?php echo ($career['score'] ?? 0); ?>%</div>
              <div class="match-label">Match Score</div>
              <p style="margin-top: var(--space-sm); font-size: var(--text-sm); color: var(--color-text-secondary);">
                <?php echo substr(htmlspecialchars($career['overview']), 0, 150); ?>...
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="section-scores" id="sectionScores">
        <!-- Career scores will be displayed here -->
      </div>

      <div class="card insights">
        <h2>Insights</h2>
        <div id="insightsList">
          <?php foreach ($insights as $insight): ?>
            <div class="insight-item"><?php echo htmlspecialchars($insight); ?></div>
          <?php endforeach; ?>
          <div class="insight-item">Based on your assessment, focus on developing skills in your top career matches.</div>
        </div>
      </div>

      <div class="cta-wrap">
        <a href="careers.php" class="btn btn-primary">See detailed career recommendations</a>
        <p style="margin-top: var(--space-md); font-size: var(--text-sm); color: var(--color-text-secondary);">
          <a href="assessment.php?retake=1" style="color: var(--color-primary);">Retake assessment</a>
        </p>
      </div>
    </main>
  </div>

  <script>
    (function() {
      // Theme
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

      // Display career scores from PHP data
      var careerScores = <?php echo json_encode($test_results['scores'] ?? []); ?>;
      var container = document.getElementById('sectionScores');
      
      // Sort careers by score (highest first)
      var sortedCareers = Object.entries(careerScores).sort((a, b) => b[1] - a[1]);
      
      sortedCareers.forEach(function([career, score]) {
        var careerName = career.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        var card = document.createElement('div');
        card.className = 'card section-card';
        card.innerHTML = `
          <h3>${careerName}</h3>
          <div class="progress-bar">
            <div class="progress-bar-fill" style="width: ${score}%;"></div>
          </div>
          <span class="text-muted" style="font-size: var(--text-sm);">${score}% match</span>
        `;
        container.appendChild(card);
      });

      var sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        function checkSidebar() { 
          if (window.innerWidth >= 1024) sidebar.style.display = 'block'; 
          else sidebar.style.display = 'none'; 
        }
        checkSidebar();
        window.addEventListener('resize', checkSidebar);
      }
      
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
      
      setTimeout(function() {
        document.getElementById('pageLoader').classList.add('hidden');
      }, 500);
    })();
  </script>
</body>
</html>; }
    .insight-item { padding: var(--space-sm) 0; border-bottom: 1px solid var(--color-border); font-size: var(--text-sm); color: var(--color-text-secondary); }
    .insight-item:last-child { border-bottom: none; }
    .cta-wrap { margin-top: var(--space-xl); text-align: center; }
    .cta-wrap .btn { min-width: 200px; }
    .page-loader { position: fixed; inset: 0; background: var(--color-bg); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, visibility 0.4s ease; }
    .page-loader.hidden { opacity: 0; visibility: hidden; }
    .skeleton-chart { height: 120px; border-radius: var(--radius-lg); margin-bottom: var(--space-md); }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="container" style="padding: var(--space-xl); width: 100%; max-width: 400px;">
      <div class="skeleton skeleton-title" style="height: 32px; margin-bottom: var(--space-lg);"></div>
      <div class="skeleton skeleton-chart"></div>
      <div class="skeleton skeleton-chart"></div>
      <div class="skeleton skeleton-chart"></div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="user-dashboard.html" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="user-dashboard.html">Dashboard</a>
          <a href="profile-setup.html">Profile</a>
          <a href="assessment-test.html">Assessment</a>
          <a href="test-result.html" class="active">Test Result</a>
          <a href="recommendations.html">Recommendations</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="recommendations.html" class="btn btn-primary">View Recommendations</a>
        <a href="user-dashboard.html" class="btn btn-ghost">Dashboard</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="user-dashboard.html">Dashboard</a>
    <a href="profile-setup.html">Profile</a>
    <a href="assessment-test.html">Take Assessment</a>
    <a href="test-result.html" class="active">Test Result</a>
    <a href="recommendations.html">Recommendations</a>
    <a href="index.html">Logout</a>
  </nav>

  <div class="main-with-sidebar">
    <aside class="sidebar" id="sidebar" style="display: none;">
      <div class="sidebar-brand">Menu</div>
      <ul class="sidebar-nav">
        <li><a href="user-dashboard.html">Dashboard</a></li>
        <li><a href="profile-setup.html">Profile</a></li>
        <li><a href="assessment-test.html">Take Assessment</a></li>
        <li><a href="test-result.html" class="active">Test Result</a></li>
        <li><a href="recommendations.html">Recommendations</a></li>
      </ul>
    </aside>
  <main class="result-page">
    <div class="result-header">
      <h1>Assessment Results</h1>
      <p class="text-muted">Your section-wise performance</p>
      <div class="overall-score" id="overallScore">--%</div>
      <p class="text-muted" id="overallLabel">Overall score</p>
    </div>

    <div class="section-scores" id="sectionScores">
      <!-- Filled by JS -->
    </div>

    <div class="card insights">
      <h2>Insights</h2>
      <div id="insightsList">
        <div class="insight-item">Your aptitude suggests strong logical and structured thinking.</div>
        <div class="insight-item">Interest alignment is high with technology and problem-solving roles.</div>
        <div class="insight-item">Skill self-assessment indicates readiness for upskilling in chosen areas.</div>
      </div>
    </div>

    <div class="cta-wrap">
      <a href="recommendations.html" class="btn btn-primary">See career recommendations</a>
    </div>
  </main>
  </div>

  <!-- Chatbot -->
  <button class="chatbot-fab" id="chatbotFab" aria-label="Open career guidance chat">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
  </button>
  <div class="chatbot-modal" id="chatbotModal">
    <div class="chatbot-panel">
      <div class="chatbot-header"><strong>Career Guide</strong><button class="btn btn-ghost" id="chatbotClose">✕</button></div>
      <div class="chatbot-messages" id="chatMessages">
        <div class="chat-bubble bot">Your results are ready! Ask me about careers that match your profile.</div>
        <div class="chatbot-quick-actions" id="quickActions">
          <button type="button" data-msg="Suggest a career">Suggest a career</button>
        </div>
      </div>
      <div class="chatbot-input-wrap">
        <form id="chatForm">
          <input type="text" id="chatInput" placeholder="Ask about careers...">
          <button type="submit">Send</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    (function() {
      // Theme
      var theme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', theme);
      var btn = document.getElementById('themeToggle');
      var sun = btn && btn.querySelector('.icon-sun');
      var moon = btn && btn.querySelector('.icon-moon');
      if (btn) {
        if (theme === 'dark') { if (sun) sun.classList.add('sr-only'); if (moon) moon.classList.remove('sr-only'); }
        else { if (sun) sun.classList.remove('sr-only'); if (moon) moon.classList.add('sr-only'); }
        btn.addEventListener('click', function() {
          var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
          document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
          localStorage.setItem('theme', isDark ? 'light' : 'dark');
          if (sun) sun.classList.toggle('sr-only', !isDark);
          if (moon) moon.classList.toggle('sr-only', isDark);
        });
      }

      // Mock section scores from stored answers (or default)
      var stored = localStorage.getItem('testAnswers');
      var answers = stored ? JSON.parse(stored) : {};
      var sections = [
        { name: 'Aptitude', key: 'aptitude', max: 4, count: 0 },
        { name: 'Interest', key: 'interest', max: 4, count: 0 },
        { name: 'Skill', key: 'skill', max: 2, count: 0 }
      ];
      var questionCategories = { 1: 'aptitude', 2: 'aptitude', 3: 'interest', 4: 'interest', 5: 'skill', 6: 'interest', 7: 'aptitude', 8: 'skill', 9: 'interest', 10: 'aptitude' };
      for (var qId in answers) {
        var cat = questionCategories[qId];
        if (cat) {
          var s = sections.find(function(x) { return x.key === cat; });
          if (s) s.count++;
        }
      }
      var totalQuestions = 10;
      var totalScore = 0;
      sections.forEach(function(s) {
        var pct = s.max > 0 ? Math.min(100, Math.round((s.count / s.max) * 100)) : 0;
        totalScore += pct;
      });
      var overallPct = Math.round(totalScore / 3);

      document.getElementById('overallScore').textContent = overallPct + '%';
      var container = document.getElementById('sectionScores');
      sections.forEach(function(s) {
        var pct = s.max > 0 ? Math.min(100, Math.round((s.count / s.max) * 100)) : 50;
        var card = document.createElement('div');
        card.className = 'card section-card';
        card.innerHTML = '<h3>' + s.name + '</h3><div class="progress-bar"><div class="progress-bar-fill" style="width: ' + pct + '%;"></div></div><span class="text-muted" style="font-size: var(--text-sm);">' + pct + '%</span>';
        container.appendChild(card);
      });

      var sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        function checkSidebar() { if (window.innerWidth >= 1024) sidebar.style.display = 'block'; else sidebar.style.display = 'none'; }
        checkSidebar();
        window.addEventListener('resize', checkSidebar);
      }
      var hamburger = document.getElementById('hamburger');
      var mobileNav = document.getElementById('mobileNav');
      if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() { hamburger.classList.toggle('open'); mobileNav.classList.toggle('open'); });
        mobileNav.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', function() { hamburger.classList.remove('open'); mobileNav.classList.remove('open'); }); });
      }
      setTimeout(function() {
        document.getElementById('pageLoader').classList.add('hidden');
      }, 500);

      // Chatbot
      var chatbotFab = document.getElementById('chatbotFab');
      var chatbotModal = document.getElementById('chatbotModal');
      var chatbotClose = document.getElementById('chatbotClose');
      var chatMessages = document.getElementById('chatMessages');
      var chatForm = document.getElementById('chatForm');
      var chatInput = document.getElementById('chatInput');
      var quickActions = document.getElementById('quickActions');
      function addMessage(text, isUser) {
        var div = document.createElement('div');
        div.className = 'chat-bubble ' + (isUser ? 'user' : 'bot');
        div.textContent = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
      chatbotFab.addEventListener('click', function() { chatbotModal.classList.add('open'); });
      chatbotClose.addEventListener('click', function() { chatbotModal.classList.remove('open'); });
      chatbotModal.addEventListener('click', function(e) { if (e.target === chatbotModal) chatbotModal.classList.remove('open'); });
      quickActions.querySelectorAll('button').forEach(function(b) {
        b.addEventListener('click', function() {
          var msg = this.getAttribute('data-msg');
          addMessage(msg, true);
          addMessage('Based on your results, check your top career recommendations on the next page!', false);
        });
      });
      chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var text = chatInput.value.trim();
        if (!text) return;
        addMessage(text, true);
        chatInput.value = '';
        addMessage('View your recommendations for careers that match your assessment.', false);
      });
    })();
  </script>
</body>
</html>
