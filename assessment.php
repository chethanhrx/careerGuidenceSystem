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

// Initialize variables
$test_submitted = false;
$test_results = [];
$suggested_careers = [];
$insights = [];
$overall_score = 0;

// Check if form was submitted (AJAX or regular POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
    $answers = json_decode($_POST['answers'], true);
    
    if (!empty($answers)) {
        // Process assessment and save to database
        $test_submitted = true;
        
        // Initialize scores for different careers
        $career_scores = [
            'software-developer' => 0,
            'data-analyst' => 0,
            'cybersecurity-analyst' => 0,
            'digital-marketer' => 0,
            'ui-ux-designer' => 0
        ];
        
        // Process each answer
        foreach ($answers as $question_id => $answer_index) {
            // Get question details
            $sql = "SELECT * FROM assessment_questions WHERE id = '$question_id'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $question = mysqli_fetch_assoc($result);
                
                // Decode career relevance
                $career_relevance = json_decode($question['career_relevance'], true);
                $weight = $question['weight'] ?? 1;
                
                // Add scores to relevant careers
                if (is_array($career_relevance)) {
                    foreach ($career_relevance as $career_slug) {
                        if (isset($career_scores[$career_slug])) {
                            // Each option gives points (0-3 for 4 options)
                            $career_scores[$career_slug] += ($answer_index * $weight);
                        }
                    }
                }
            }
        }
        
        // Normalize scores to 0-100
        $max_score = count($answers) * 3 * 1; // Assuming weight is 1 for all
        $final_scores = [];
        
        foreach ($career_scores as $career => $score) {
            $percentage = $max_score > 0 ? round(($score / $max_score) * 100) : 0;
            $final_scores[$career] = min(100, $percentage);
        }
        
        // Sort careers by score (descending)
        arsort($final_scores);
        
        // Get top 3 careers
        $suggested_careers_slugs = array_slice(array_keys($final_scores), 0, 3);
        
        // Get career details for suggested careers
        $suggested_careers = [];
        foreach ($suggested_careers_slugs as $career_slug) {
            $sql_career = "SELECT * FROM careers WHERE slug = '$career_slug'";
            $result_career = mysqli_query($conn, $sql_career);
            if ($result_career && mysqli_num_rows($result_career) > 0) {
                $career_data = mysqli_fetch_assoc($result_career);
                $career_data['score'] = $final_scores[$career_slug] ?? 0;
                $suggested_careers[] = $career_data;
            }
        }
        
        // Calculate overall score
        $total_score = 0;
        $count = 0;
        foreach ($final_scores as $career => $score) {
            $total_score += $score;
            $count++;
        }
        $overall_score = $count > 0 ? round($total_score / $count) : 0;
        
        // Prepare insights
        if ($overall_score >= 80) {
            $insights[] = "Excellent! Your assessment shows strong alignment with multiple career paths.";
        } elseif ($overall_score >= 60) {
            $insights[] = "Good match! You have solid foundations for several career options.";
        } else {
            $insights[] = "Your assessment shows potential in various areas.";
        }
        
        if (!empty($final_scores)) {
            $top_career_key = array_key_first($final_scores);
            if ($top_career_key) {
                $career_name = str_replace('-', ' ', $top_career_key);
                $career_name = ucwords($career_name);
                $insights[] = "Your strongest match is with $career_name careers.";
            }
        }
        $insights[] = "Based on your assessment, focus on developing skills in your top career matches.";
        
        // Prepare results array for database
        $results = [
            'scores' => $final_scores,
            'suggested_careers' => $suggested_careers_slugs,
            'assessment_date' => date('Y-m-d H:i:s'),
            'total_questions' => count($answers),
            'answers' => $answers,
            'overall_score' => $overall_score,
            'insights' => $insights
        ];
        
        // Save to database
        $results_json = mysqli_real_escape_string($conn, json_encode($results));
        $sql = "INSERT INTO user_tests (user_id, test_type, results, completed_at) 
                VALUES ('$user_id', 'career_assessment', '$results_json', NOW())";
        if (mysqli_query($conn, $sql)) {
            // Store in session for displaying results
            $_SESSION['last_test_results'] = $results;
            $_SESSION['test_submitted'] = true;
            $_SESSION['last_test_time'] = time();
        } else {
            error_log("Database error: " . mysqli_error($conn));
        }
        
        // If this is an AJAX request, send JSON response instead of HTML
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => 'assessment.php?submitted=true']);
            exit();
        } else {
            // Redirect to avoid form resubmission
            header("Location: assessment.php?submitted=true");
            exit();
        }
    }
}

// Check if test was just submitted
if (isset($_GET['submitted']) && $_GET['submitted'] === 'true' && isset($_SESSION['last_test_results'])) {
    $test_submitted = true;
    $test_results = $_SESSION['last_test_results'];
    $overall_score = $test_results['overall_score'] ?? 0;
    $insights = $test_results['insights'] ?? [];
    
    // Get suggested careers from results
    if (isset($test_results['suggested_careers'])) {
        $suggested_careers = [];
        foreach ($test_results['suggested_careers'] as $career_slug) {
            $sql_career = "SELECT * FROM careers WHERE slug = '$career_slug'";
            $result_career = mysqli_query($conn, $sql_career);
            if ($result_career && mysqli_num_rows($result_career) > 0) {
                $career_data = mysqli_fetch_assoc($result_career);
                $career_data['score'] = $test_results['scores'][$career_slug] ?? 0;
                $suggested_careers[] = $career_data;
            }
        }
    }
}

// Check for existing test results in session (for page refresh)
if (!$test_submitted && isset($_SESSION['last_test_results'])) {
    // Check if last test was within 1 hour
    if (isset($_SESSION['last_test_time']) && (time() - $_SESSION['last_test_time']) < 3600) {
        $test_submitted = true;
        $test_results = $_SESSION['last_test_results'];
        $overall_score = $test_results['overall_score'] ?? 0;
        $insights = $test_results['insights'] ?? [];
        
        // Get suggested careers from results
        if (isset($test_results['suggested_careers'])) {
            $suggested_careers = [];
            foreach ($test_results['suggested_careers'] as $career_slug) {
                $sql_career = "SELECT * FROM careers WHERE slug = '$career_slug'";
                $result_career = mysqli_query($conn, $sql_career);
                if ($result_career && mysqli_num_rows($result_career) > 0) {
                    $career_data = mysqli_fetch_assoc($result_career);
                    $career_data['score'] = $test_results['scores'][$career_slug] ?? 0;
                    $suggested_careers[] = $career_data;
                }
            }
        }
    }
}

// Fetch questions from database (only if test not submitted)
if (!$test_submitted) {
    $questions = [];
    $sql_questions = "SELECT * FROM assessment_questions ORDER BY sort_order ASC, id ASC LIMIT 20";
    $result_questions = mysqli_query($conn, $sql_questions);

    if ($result_questions && mysqli_num_rows($result_questions) > 0) {
        while ($row = mysqli_fetch_assoc($result_questions)) {
            // Parse options JSON if needed
            if (isset($row['options']) && is_string($row['options'])) {
                try {
                    $row['options'] = json_decode($row['options'], true);
                } catch (Exception $e) {
                    $row['options'] = ["Strongly Disagree", "Disagree", "Agree", "Strongly Agree"];
                }
            }
            $questions[] = $row;
        }
    }
    
    $questions_json = json_encode($questions);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Assessment | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Assessment Styles */
    .test-page { padding: var(--space-lg); max-width: 720px; margin: 0 auto; }
    .test-header { margin-bottom: var(--space-xl); text-align: center; }
    .progress-wrap { margin-bottom: var(--space-lg); }
    .progress-bar { height: 10px; border-radius: var(--radius-full); background: var(--color-border); overflow: hidden; }
    .progress-bar-fill { height: 100%; background: var(--color-primary); border-radius: var(--radius-full); width: 5%; transition: width 0.3s ease; }
    .test-meta { display: flex; justify-content: space-between; font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: var(--space-md); }
    .timer { font-weight: 600; color: var(--color-primary); }
    .question-card { padding: var(--space-xl); margin-bottom: var(--space-lg); }
    .question-card h2 { font-size: var(--text-lg); margin-bottom: var(--space-md); }
    .options { display: flex; flex-direction: column; gap: var(--space-sm); }
    .option { display: flex; align-items: center; padding: var(--space-md) var(--space-lg); border: 2px solid var(--color-border); border-radius: var(--radius-lg); cursor: pointer; transition: all var(--transition-fast); min-height: 52px; }
    .option:hover { border-color: var(--color-primary); background: var(--color-primary-light); }
    .option.selected { border-color: var(--color-primary); background: var(--color-primary-light); color: var(--color-primary); font-weight: 500; }
    .option input { margin-right: var(--space-md); }
    .test-nav { display: flex; justify-content: space-between; align-items: center; margin-top: var(--space-xl); gap: var(--space-md); }
    .test-nav .btn { min-width: 120px; }
    .page-loader { position: fixed; inset: 0; background: var(--color-bg); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, visibility 0.4s ease; }
    .page-loader.hidden { opacity: 0; visibility: hidden; }
    
    /* Results Styles */
    .result-page { padding: var(--space-lg); max-width: 720px; margin: 0 auto; }
    .result-header { text-align: center; margin-bottom: var(--space-2xl); }
    .overall-score { font-size: 48px; font-weight: 700; color: var(--color-primary); margin: var(--space-md) 0; }
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
    .career-matches { margin-top: var(--space-xl); }
    .career-match-card { padding: var(--space-lg); margin-bottom: var(--space-md); }
    .career-match-card h3 { margin-bottom: var(--space-sm); }
    .match-score { font-size: 32px; font-weight: 700; color: var(--color-primary); }
    .match-label { font-size: var(--text-sm); color: var(--color-text-secondary); }
    
    /* Toggle between assessment and results */
    .assessment-container { display: none; }
    .results-container { display: none; }
    .assessment-container.active, .results-container.active { display: block; }
    
    /* Additional result styles */
    .career-matches-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-md); margin-top: var(--space-md); }
    .career-match { background: var(--color-bg-light); padding: var(--space-lg); border-radius: var(--radius-lg); border: 1px solid var(--color-border); }
    .career-match h4 { margin: 0 0 var(--space-sm) 0; font-size: var(--text-base); }
    .match-percentage { font-size: 24px; font-weight: 600; color: var(--color-primary); }
    .progress-label { display: flex; justify-content: space-between; margin-top: var(--space-xs); font-size: var(--text-sm); color: var(--color-text-secondary); }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="container" style="padding: var(--space-xl);">
      <div class="skeleton skeleton-title" style="width: 60%; height: 28px; margin-bottom: var(--space-lg);"></div>
      <div class="progress-bar skeleton" style="margin-bottom: var(--space-xl);"></div>
      <div class="skeleton-question card">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text"></div>
      </div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="profile-setup.php">Profile</a>
          <a href="assessment.php" class="active">Assessment</a>
          <a href="career-details.php">Careers</a>
          <a href="test-results.php">Results</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <span id="headerTimer" class="timer" style="display: <?php echo $test_submitted ? 'none' : 'inline'; ?>">15:00</span>
        <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile-setup.php">Profile</a>
    <a href="assessment.php" class="active">Assessment</a>
    <a href="career-details.php">Careers</a>
    <a href="test-results.php">Results</a>
    <a href="./logout.php">Logout</a>
  </nav>

  <!-- Assessment Container -->
  <div class="assessment-container <?php echo $test_submitted ? '' : 'active'; ?>" id="assessmentContainer">
    <main class="test-page">
      <div class="test-header">
        <h1>Career Assessment</h1>
        <p class="text-muted">Answer honestly. There are no wrong answers.</p>
      </div>

      <form id="assessmentForm" method="post">
        <div class="progress-wrap">
          <div class="test-meta">
            <span>Question <span id="currentQ">1</span> of <span id="totalQ">20</span></span>
            <span class="timer" id="timer">15:00</span>
          </div>
          <div class="progress-bar">
            <div class="progress-bar-fill" id="progressFill" style="width: 5%;"></div>
          </div>
        </div>

        <div class="question-card card" id="questionCard">
          <h2 id="questionText">Loading questions...</h2>
          <div class="options" id="optionsContainer">
            <div class="option">
              <input type="radio" disabled> Loading...
            </div>
          </div>
        </div>
        
        <div class="test-nav">
          <button type="button" class="btn btn-secondary" id="prevBtn" disabled>Previous</button>
          <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
        </div>
      </form>
    </main>
  </div>

  <!-- Results Container -->
  <div class="results-container <?php echo $test_submitted ? 'active' : ''; ?>" id="resultsContainer">
    <main class="result-page">
      <div class="result-header">
        <h1>Assessment Results</h1>
        <p class="text-muted">Your career assessment analysis</p>
        <div class="overall-score" id="overallScore"><?php echo $overall_score; ?>%</div>
        <p class="text-muted" id="overallLabel">Overall Match Score</p>
        <p class="text-muted" style="font-size: var(--text-sm);">
          Assessment completed: <?php echo date('d M Y, h:i A'); ?>
        </p>
      </div>

      <?php if (!empty($suggested_careers)): ?>
        <div class="career-matches">
          <h2 style="margin-bottom: var(--space-lg);">Top Career Matches</h2>
          <div class="career-matches-grid">
            <?php foreach ($suggested_careers as $career): ?>
              <div class="career-match">
                <h4><?php echo htmlspecialchars($career['title']); ?></h4>
                <div class="match-percentage"><?php echo ($career['score'] ?? 0); ?>%</div>
                <div class="progress-bar">
                  <div class="progress-bar-fill" style="width: <?php echo ($career['score'] ?? 0); ?>%;"></div>
                </div>
                <div class="progress-label">
                  <span>Match Score</span>
                  <span><?php echo ($career['score'] ?? 0); ?>%</span>
                </div>
                <p style="margin-top: var(--space-sm); font-size: var(--text-sm); color: var(--color-text-secondary); line-height: 1.4;">
                  <?php echo htmlspecialchars(substr($career['overview'] ?? 'No description available', 0, 120)); ?>...
                </p>
                <a href="career-details.php?slug=<?php echo $career['slug']; ?>" class="btn btn-outline" style="margin-top: var(--space-sm); display: inline-block;">
                  Learn More
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($test_results['scores']) && is_array($test_results['scores'])): ?>
        <div class="section-scores">
          <h2 style="margin-bottom: var(--space-lg);">Detailed Career Scores</h2>
          <?php foreach ($test_results['scores'] as $career_slug => $score): 
            $career_name = ucwords(str_replace('-', ' ', $career_slug));
          ?>
            <div class="card section-card">
              <h3><?php echo $career_name; ?></h3>
              <div class="progress-bar">
                <div class="progress-bar-fill" style="width: <?php echo $score; ?>%;"></div>
              </div>
              <div class="progress-label">
                <span>Compatibility Score</span>
                <span><?php echo $score; ?>%</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($insights)): ?>
        <div class="card insights">
          <h2>Assessment Insights</h2>
          <div id="insightsList">
            <?php foreach ($insights as $insight): ?>
              <div class="insight-item">• <?php echo htmlspecialchars($insight); ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="cta-wrap">
        <a href="career-details.php" class="btn btn-primary">Explore All Careers</a>
        <button id="retakeTest" class="btn btn-outline" style="margin-top: var(--space-md);">Retake Assessment</button>
        <a href="dashboard.php" class="btn btn-ghost" style="margin-top: var(--space-sm); display: block;">Return to Dashboard</a>
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

      <?php if (!$test_submitted): ?>
      // Questions data from PHP
      const questions = <?php echo isset($questions_json) ? $questions_json : '[]'; ?>;
      const total = questions.length;
      let currentIndex = 0;
      let answers = {};
      let timerSeconds = 15 * 60;
      let timerInterval = null;

      function startTimer() {
        if (timerInterval) clearInterval(timerInterval);
        updateTimerDisplay();
        timerInterval = setInterval(function() {
          timerSeconds--;
          updateTimerDisplay();
          if (timerSeconds <= 0) {
            clearInterval(timerInterval);
            submitTest();
          }
        }, 1000);
      }

      function updateTimerDisplay() {
        var m = Math.floor(timerSeconds / 60);
        var s = timerSeconds % 60;
        var str = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        var el = document.getElementById('timer');
        var headerEl = document.getElementById('headerTimer');
        if (el) el.textContent = str;
        if (headerEl) headerEl.textContent = str;
      }

      function renderQuestion() {
        if (questions.length === 0 || currentIndex >= questions.length) {
          document.getElementById('questionText').textContent = 'No questions available.';
          document.getElementById('nextBtn').disabled = true;
          return;
        }

        const q = questions[currentIndex];
        document.getElementById('questionText').textContent = q.question_text;
        document.getElementById('currentQ').textContent = currentIndex + 1;
        document.getElementById('totalQ').textContent = total;
        
        // Update progress bar
        const progressPercent = ((currentIndex + 1) / total * 100);
        document.getElementById('progressFill').style.width = progressPercent + '%';

        var container = document.getElementById('optionsContainer');
        container.innerHTML = '';
        
        // Get options - they should already be parsed by PHP
        let options = q.options;
        
        if (Array.isArray(options) && options.length > 0) {
          options.forEach(function(opt, i) {
            var label = document.createElement('label');
            label.className = 'option' + (answers[q.id] === i ? ' selected' : '');
            
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'q' + q.id;
            input.value = i;
            input.checked = answers[q.id] === i;
            
            label.appendChild(input);
            label.appendChild(document.createTextNode(opt));
            
            label.addEventListener('click', function() {
              // Remove selection from all options
              container.querySelectorAll('.option').forEach(function(o) { 
                o.classList.remove('selected'); 
              });
              
              // Select this option
              label.classList.add('selected');
              input.checked = true;
              
              // Store answer
              answers[q.id] = i;
            });
            
            container.appendChild(label);
          });
        } else {
          container.innerHTML = '<div class="text-muted">No options available for this question.</div>';
        }

        // Update navigation buttons
        document.getElementById('prevBtn').disabled = currentIndex === 0;
        
        if (currentIndex === total - 1) {
          document.getElementById('nextBtn').textContent = 'Submit Test';
          document.getElementById('nextBtn').classList.add('btn-success');
        } else {
          document.getElementById('nextBtn').textContent = 'Next';
          document.getElementById('nextBtn').classList.remove('btn-success');
        }
      }

      function submitTest() {
        if (timerInterval) clearInterval(timerInterval);
        
        // Check if all questions answered
        const answeredCount = Object.keys(answers).length;
        if (answeredCount < total) {
          if (!confirm(`You have answered ${answeredCount} out of ${total} questions. Submit anyway?`)) {
            // Restart timer if user cancels submission
            startTimer();
            return;
          }
        }
        
        // Disable buttons to prevent double submission
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) {
          nextBtn.disabled = true;
          nextBtn.textContent = 'Submitting...';
        }
        
        // Show loading
        document.getElementById('pageLoader').classList.remove('hidden');
        
        // Submit via AJAX
        const formData = new FormData();
        formData.append('answers', JSON.stringify(answers));
        
        fetch('assessment.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.success && data.redirect) {
            // Redirect to results page
            window.location.href = data.redirect;
          } else {
            // Reload the page to show results
            window.location.reload();
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error submitting test. Please try again.');
          
          // Re-enable buttons
          if (prevBtn) prevBtn.disabled = false;
          if (nextBtn) {
            nextBtn.disabled = false;
            nextBtn.textContent = currentIndex === total - 1 ? 'Submit Test' : 'Next';
          }
          
          // Hide loader
          document.getElementById('pageLoader').classList.add('hidden');
          
          // Restart timer
          startTimer();
        });
      }

      // Navigation button event listeners
      document.getElementById('prevBtn').addEventListener('click', function() {
        if (currentIndex > 0) {
          currentIndex--;
          renderQuestion();
        }
      });

      document.getElementById('nextBtn').addEventListener('click', function() {
        // Check if current question is answered
        const currentQ = questions[currentIndex];
        if (!answers[currentQ.id] && answers[currentQ.id] !== 0) {
          alert('Please select an answer before proceeding.');
          return;
        }
        
        if (currentIndex < total - 1) {
          currentIndex++;
          renderQuestion();
        } else {
          submitTest();
        }
      });

      // Initialize assessment
      if (questions.length > 0) {
        renderQuestion();
        startTimer();
      } else {
        document.getElementById('questionText').textContent = 'No assessment questions available. Please contact administrator.';
        document.getElementById('nextBtn').disabled = true;
      }
      
      <?php endif; ?>

      // Retake test button
      document.getElementById('retakeTest')?.addEventListener('click', function() {
        // Clear test session and reload for fresh test
        fetch('clear-test.php')
          .then(response => response.text())
          .then(() => {
            window.location.reload();
          })
          .catch(error => {
            console.error('Error:', error);
            window.location.reload();
          });
      });

      // Initialize page loader
      setTimeout(function() {
        document.getElementById('pageLoader').classList.add('hidden');
      }, 500);

      // Add keyboard navigation
      document.addEventListener('keydown', function(e) {
        if (!<?php echo $test_submitted ? 'false' : 'true'; ?>) {
          if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            document.getElementById('prevBtn').click();
          } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('nextBtn').click();
          }
        }
      });

    })();
  </script>
</body>
</html>