<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $education = mysqli_real_escape_string($conn, trim($_POST['education']));
    $stream = mysqli_real_escape_string($conn, trim($_POST['stream']));
    $career_goal = mysqli_real_escape_string($conn, trim($_POST['careerGoal']));
    
    // Get selected skills and interests
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $interests = isset($_POST['interests']) ? $_POST['interests'] : [];
    
    // Validation
    if (empty($education)) {
        $errors['education'] = "Education level is required";
    }
    
    if (empty($stream)) {
        $errors['stream'] = "Stream is required";
    }
    
    if (empty($career_goal)) {
        $errors['careerGoal'] = "Career goal is required";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Convert arrays to JSON for storage
        $skills_json = json_encode($skills);
        $interests_json = json_encode($interests);
        
        // Check if user already has a profile
        $check_sql = "SELECT id FROM user_profiles WHERE user_id = '$user_id'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing profile
            $sql = "UPDATE user_profiles SET 
                    education = '$education',
                    stream = '$stream',
                    career_goal = '$career_goal',
                    skills = '$skills_json',
                    interests = '$interests_json',
                    updated_at = NOW()
                    WHERE user_id = '$user_id'";
        } else {
            // Insert new profile
            $sql = "INSERT INTO user_profiles (user_id, education, stream, career_goal, skills, interests, created_at) 
                    VALUES ('$user_id', '$education', '$stream', '$career_goal', '$skills_json', '$interests_json', NOW())";
        }
        
        if (mysqli_query($conn, $sql)) {
            // Mark profile as complete in session
            $_SESSION['profile_complete'] = true;
            
            // Also save skills to user_skills table
            foreach ($skills as $skill) {
                $skill_name = mysqli_real_escape_string($conn, $skill);
                $check_skill = "SELECT id FROM user_skills WHERE user_id = '$user_id' AND skill_name = '$skill_name'";
                $skill_result = mysqli_query($conn, $check_skill);
                
                if (mysqli_num_rows($skill_result) == 0) {
                    $skill_sql = "INSERT INTO user_skills (user_id, skill_name, proficiency, created_at) 
                                 VALUES ('$user_id', '$skill_name', 'beginner', NOW())";
                    mysqli_query($conn, $skill_sql);
                }
            }
            
            $success = "Profile saved successfully!";
            // Redirect to dashboard after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            $errors['database'] = "Error saving profile: " . mysqli_error($conn);
        }
    }
}

// Get existing profile data if available
$profile_data = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM user_profiles WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $profile_data = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Setup | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .profile-page { padding: var(--space-lg); max-width: 640px; margin: 0 auto; }
    .profile-page h1 { margin-bottom: var(--space-sm); }
    .profile-page .subtitle { color: var(--color-text-secondary); margin-bottom: var(--space-xl); }
    .form-section { margin-bottom: var(--space-2xl); }
    .form-section h2 { font-size: var(--text-lg); margin-bottom: var(--space-md); }
    .chips { display: flex; flex-wrap: wrap; gap: var(--space-sm); }
    .chip { display: inline-flex; align-items: center; padding: var(--space-sm) var(--space-md); font-size: var(--text-sm); background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); border-radius: var(--radius-full); cursor: pointer; transition: all var(--transition-fast); }
    .chip:hover { border-color: var(--color-primary); color: var(--color-primary); }
    .chip.selected { background: var(--color-primary-light); border-color: var(--color-primary); color: var(--color-primary); font-weight: 500; }
    .chip input { display: none; }
    .sticky-cta { position: sticky; bottom: 0; padding: var(--space-lg) 0; background: var(--color-bg); margin-top: var(--space-xl); }
    .page-loader { position: fixed; inset: 0; background: var(--color-bg); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, visibility 0.4s ease; }
    .page-loader.hidden { opacity: 0; visibility: hidden; }
    .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
    .form-error { color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: block; }
    .form-select.error { border-color: #dc3545; }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 280px; height: 400px; border-radius: var(--radius-xl);"></div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="profile-setup.php" class="active">Profile</a>
          <a href="assessment.php">Assessment</a>
          <a href="careers.php">Careers</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
        <a href="logout.php" class="btn btn-ghost">Logout</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile-setup.php" class="active">Profile</a>
    <a href="assessment.php">Take Assessment</a>
    <a href="careers.php">Careers</a>
    <a href="logout.php">Logout</a>
  </nav>

  <main class="profile-page">
    <h1>Profile Setup</h1>
    <p class="subtitle">Help us personalize your career recommendations.</p>
    
    <?php if ($success): ?>
      <div class="alert-success">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($errors['database'])): ?>
      <div class="alert-danger">
        <?php echo htmlspecialchars($errors['database']); ?>
      </div>
    <?php endif; ?>

    <form id="profileForm" method="post" action="">
      <div class="form-section">
        <h2>Education level</h2>
        <div class="form-group">
          <label class="form-label">Select your current or highest education</label>
          <select name="education" id="education" class="form-select <?php echo isset($errors['education']) ? 'error' : ''; ?>" required>
            <option value="">Choose...</option>
            <option value="10th" <?php echo (isset($profile_data['education']) && $profile_data['education'] == '10th') ? 'selected' : ''; ?>>10th / Matriculation</option>
            <option value="12th" <?php echo (isset($profile_data['education']) && $profile_data['education'] == '12th') ? 'selected' : ''; ?>>12th / Higher Secondary</option>
            <option value="degree" <?php echo (isset($profile_data['education']) && $profile_data['education'] == 'degree') ? 'selected' : ''; ?>>Graduate (Degree)</option>
            <option value="postgrad" <?php echo (isset($profile_data['education']) && $profile_data['education'] == 'postgrad') ? 'selected' : ''; ?>>Post Graduate</option>
          </select>
          <?php if (isset($errors['education'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['education']); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-section">
        <h2>Stream</h2>
        <div class="form-group">
          <label class="form-label">Your stream / field</label>
          <select name="stream" id="stream" class="form-select <?php echo isset($errors['stream']) ? 'error' : ''; ?>" required>
            <option value="">Choose...</option>
            <option value="science" <?php echo (isset($profile_data['stream']) && $profile_data['stream'] == 'science') ? 'selected' : ''; ?>>Science</option>
            <option value="commerce" <?php echo (isset($profile_data['stream']) && $profile_data['stream'] == 'commerce') ? 'selected' : ''; ?>>Commerce</option>
            <option value="arts" <?php echo (isset($profile_data['stream']) && $profile_data['stream'] == 'arts') ? 'selected' : ''; ?>>Arts / Humanities</option>
            <option value="cs" <?php echo (isset($profile_data['stream']) && $profile_data['stream'] == 'cs') ? 'selected' : ''; ?>>Computer Science / IT</option>
            <option value="other" <?php echo (isset($profile_data['stream']) && $profile_data['stream'] == 'other') ? 'selected' : ''; ?>>Other</option>
          </select>
          <?php if (isset($errors['stream'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['stream']); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-section">
        <h2>Skills (select all that apply)</h2>
        <div class="chips" id="skillsChips">
          <!-- Skills will be added by JavaScript -->
        </div>
      </div>

      <div class="form-section">
        <h2>Interests (select all that apply)</h2>
        <div class="chips" id="interestsChips">
          <!-- Interests will be added by JavaScript -->
        </div>
      </div>

      <div class="form-section">
        <h2>Career goal</h2>
        <div class="form-group">
          <label class="form-label">What do you aim for?</label>
          <select name="careerGoal" id="careerGoal" class="form-select <?php echo isset($errors['careerGoal']) ? 'error' : ''; ?>" required>
            <option value="">Choose...</option>
            <option value="job" <?php echo (isset($profile_data['career_goal']) && $profile_data['career_goal'] == 'job') ? 'selected' : ''; ?>>Job / Employment</option>
            <option value="higher_studies" <?php echo (isset($profile_data['career_goal']) && $profile_data['career_goal'] == 'higher_studies') ? 'selected' : ''; ?>>Higher studies</option>
            <option value="entrepreneurship" <?php echo (isset($profile_data['career_goal']) && $profile_data['career_goal'] == 'entrepreneurship') ? 'selected' : ''; ?>>Entrepreneurship</option>
            <option value="undecided" <?php echo (isset($profile_data['career_goal']) && $profile_data['career_goal'] == 'undecided') ? 'selected' : ''; ?>>Still exploring</option>
          </select>
          <?php if (isset($errors['careerGoal'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['careerGoal']); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="sticky-cta">
        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Save profile</button>
      </div>
    </form>
  </main>

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
      
      // Mobile menu
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

      // Skills and interests data
      var skills = ['Communication', 'Problem solving', 'Python', 'JavaScript', 'Data analysis', 'Design', 'Leadership', 'Writing', 'Excel', 'Marketing'];
      var interests = ['Technology', 'Design', 'Business', 'Science', 'Creative arts', 'Teaching', 'Healthcare', 'Finance', 'Writing', 'Research'];
      
      // Get previously selected skills and interests from PHP
      var selectedSkills = <?php echo isset($profile_data['skills']) ? $profile_data['skills'] : '[]'; ?>;
      var selectedInterests = <?php echo isset($profile_data['interests']) ? $profile_data['interests'] : '[]'; ?>;

      function renderChips(containerId, options, name, selectedValues) {
        var container = document.getElementById(containerId);
        container.innerHTML = '';
        
        options.forEach(function(opt) {
          var label = document.createElement('label');
          label.className = 'chip';
          
          var input = document.createElement('input');
          input.type = 'checkbox';
          input.name = name + '[]';
          input.value = opt;
          
          // Check if previously selected
          if (selectedValues.includes(opt)) {
            input.checked = true;
            label.classList.add('selected');
          }
          
          label.appendChild(input);
          label.appendChild(document.createTextNode(opt));
          
          label.addEventListener('click', function() {
            input.checked = !input.checked;
            label.classList.toggle('selected', input.checked);
          });
          
          container.appendChild(label);
        });
      }
      
      // Render chips
      renderChips('skillsChips', skills, 'skills', selectedSkills);
      renderChips('interestsChips', interests, 'interests', selectedInterests);

      // Page loader
      var loader = document.getElementById('pageLoader');
      if (loader) window.addEventListener('load', function() { 
        setTimeout(function() { 
          loader.classList.add('hidden'); 
        }, 300); 
      });

      // Form submission
      document.getElementById('profileForm').addEventListener('submit', function(e) {
        var hasError = false;
        
        // Clear previous error styling
        document.querySelectorAll('.form-select').forEach(function(select) {
          select.classList.remove('error');
        });
        
        // Validate required fields
        var education = document.getElementById('education').value;
        var stream = document.getElementById('stream').value;
        var careerGoal = document.getElementById('careerGoal').value;
        
        if (!education) {
          document.getElementById('education').classList.add('error');
          hasError = true;
        }
        if (!stream) {
          document.getElementById('stream').classList.add('error');
          hasError = true;
        }
        if (!careerGoal) {
          document.getElementById('careerGoal').classList.add('error');
          hasError = true;
        }
        
        if (hasError) {
          e.preventDefault();
        } else {
          var submitBtn = document.getElementById('submitBtn');
          submitBtn.disabled = true;
          submitBtn.textContent = 'Saving...';
        }
      });
    })();
  </script>
</body>
</html>