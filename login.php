<?php
session_start();
require_once 'config.php';

$errors = [];
$old_data = [];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    
    // Validation
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    }
    
    // Store old data for repopulation
    $old_data['email'] = $email;
    
    // If no errors, attempt login
    if (empty($errors)) {
        // Query to check user
        $sql = "SELECT id, name, email, password_hash, is_admin FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect based on user type
                if ($user['is_admin'] == 1) {
                    header("Location: admin/index.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $errors['password'] = "Invalid email or password";
            }
        } else {
            $errors['email'] = "Invalid email or password";
        }
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin'] == 1) {
        header("Location: admin/index.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .auth-page { min-height: calc(100vh - var(--header-height)); display: flex; align-items: center; justify-content: center; padding: var(--space-xl) var(--space-md); }
    .auth-card { width: 100%; max-width: 400px; }
    .auth-card h1 { margin-bottom: var(--space-sm); }
    .auth-card .subtitle { color: var(--color-text-secondary); margin-bottom: var(--space-xl); }
    .auth-card .btn-block { margin-top: var(--space-md); }
    .auth-links { text-align: center; margin-top: var(--space-lg); font-size: var(--text-sm); }
    .auth-links a { display: inline-block; margin: 0 var(--space-sm); }
    .page-loader { position: fixed; inset: 0; background: var(--color-bg); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, visibility 0.4s ease; }
    .page-loader.hidden { opacity: 0; visibility: hidden; }
    .form-error { color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: block; }
    .form-input.error { border-color: #dc3545; }
    .alert-danger { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
    .form-row { display: flex; justify-content: space-between; align-items: center; margin: var(--space-md) 0; }
    .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.875rem; }
    .checkbox-label input[type="checkbox"] { width: 16px; height: 16px; }
    .text-link { color: var(--color-primary); text-decoration: none; font-size: 0.875rem; }
    .text-link:hover { text-decoration: underline; }
    .password-input-wrapper { position: relative; }
    .password-toggle { position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--color-text-secondary); cursor: pointer; padding: var(--space-xs); }
    .password-toggle:hover { color: var(--color-primary); }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 280px; height: 200px; border-radius: var(--radius-xl);"></div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="index.php">Home</a>
          <a href="register.php">Register</a>
          <a href="test-results.php">Results</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="index.php" class="btn btn-ghost">Home</a>
        <a href="register.php" class="btn btn-primary">Register</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="test-results.php">Results</a>
  </nav>

  <main class="auth-page">
    <div class="card auth-card">
      <h1>Welcome back</h1>
      <p class="subtitle">Sign in to continue to your career dashboard.</p>
      
      <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
          Registration successful! Please log in with your credentials.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
          You have been logged out successfully.
        </div>
      <?php endif; ?>
      
      <form id="loginForm" method="post" action="">
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                 placeholder="you@example.com" required autocomplete="email"
                 value="<?php echo isset($old_data['email']) ? htmlspecialchars($old_data['email']) : ''; ?>">
          <?php if (isset($errors['email'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['email']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-input-wrapper">
            <input type="password" id="password" name="password" class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
              <svg class="icon-eye" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <?php if (isset($errors['password'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['password']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-row">
          <label class="checkbox-label">
            <input type="checkbox" name="remember" id="remember">
            <span>Remember me</span>
          </label>
          <a href="forgot-password.php" class="text-link">Forgot password?</a>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Sign In</button>
      </form>
      
      <div class="auth-links">
        <a href="register.php">Create account</a>
        <a href="admin/login.php">Admin login</a>
      </div>
    </div>
  </main>

  <script>
    (function() {
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
      
      var loader = document.getElementById('pageLoader');
      if (loader) window.addEventListener('load', function() { 
        setTimeout(function() { 
          loader.classList.add('hidden'); 
        }, 300); 
      });

      // Password visibility toggle
      var passwordToggle = document.getElementById('passwordToggle');
      var passwordInput = document.getElementById('password');
      if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
          var type = passwordInput.type === 'password' ? 'text' : 'password';
          passwordInput.type = type;
          var icon = this.querySelector('.icon-eye');
          if (type === 'text') {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
          } else {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
          }
          this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
        });
      }

      // Client-side validation
      document.getElementById('loginForm').addEventListener('submit', function(e) {
        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        
        // Clear previous error styling
        document.querySelectorAll('.form-input').forEach(function(input) {
          input.classList.remove('error');
        });
        
        var hasError = false;
        
        if (!email) {
          document.getElementById('email').classList.add('error');
          hasError = true;
        }
        if (!password) {
          document.getElementById('password').classList.add('error');
          hasError = true;
        }
        
        if (!hasError) {
          var submitBtn = document.getElementById('submitBtn');
          submitBtn.disabled = true;
          submitBtn.textContent = 'Signing in...';
        }
      });
    })();
  </script>
</body>
</html>