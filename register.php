<?php
session_start();
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$old_data = [];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validation
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors['name'] = "Name must be at least 2 characters";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one number";
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm'] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors['email'])) {
        $checkEmail = "SELECT id FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $checkEmail);
        if ($result && mysqli_num_rows($result) > 0) {
            $errors['email'] = "Email already registered";
        }
    }
    
    // Store old data for repopulation
    $old_data = [
        'name' => $name,
        'email' => $email
    ];
    
    // If no errors, register user
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database - match your exact table structure
        $sql = "INSERT INTO users (name, email, password_hash, is_admin) 
                VALUES ('$name', '$email', '$hashedPassword', 0)";
        
        if (mysqli_query($conn, $sql)) {
            // Get the inserted user ID
            $user_id = mysqli_insert_id($conn);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_admin'] = 0;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Create default profile record (only if table exists)
            $profile_sql = "INSERT INTO user_profiles (user_id) VALUES ('$user_id')";
            @mysqli_query($conn, $profile_sql); // Use @ to suppress error if table doesn't exist
            
            // Redirect to dashboard
            header("Location: dashboard.php?registered=success");
            exit();
        } else {
            $errors['database'] = "Registration failed. Please try again. Error: " . mysqli_error($conn);
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .auth-page { 
      min-height: calc(100vh - var(--header-height)); 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      padding: var(--space-xl) var(--space-md); 
    }
    .auth-card { 
      width: 100%; 
      max-width: 400px; 
    }
    .auth-card h1 { 
      margin-bottom: var(--space-sm); 
    }
    .auth-card .subtitle { 
      color: var(--color-text-secondary); 
      margin-bottom: var(--space-xl); 
    }
    .auth-card .btn-block { 
      margin-top: var(--space-md); 
    }
    .auth-links { 
      text-align: center; 
      margin-top: var(--space-lg); 
      font-size: var(--text-sm); 
    }
    .auth-links a { 
      display: inline-block; 
      margin: 0 var(--space-sm); 
      color: var(--color-primary);
      text-decoration: none;
    }
    .auth-links a:hover {
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
    .form-error { 
      color: #dc3545; 
      font-size: 0.875rem; 
      margin-top: 0.25rem; 
      display: block; 
    }
    .form-input.error { 
      border-color: #dc3545; 
    }
    .alert { 
      padding: var(--space-md); 
      border-radius: var(--radius-md); 
      margin-bottom: var(--space-lg); 
    }
    .alert-danger { 
      background: #f8d7da; 
      color: #721c24; 
      border: 1px solid #f5c6cb; 
    }
    .alert-success { 
      background: #d4edda; 
      color: #155724; 
      border: 1px solid #c3e6cb; 
    }
    .password-requirements {
      font-size: 0.75rem;
      color: var(--color-text-secondary);
      margin-top: 4px;
    }
    .password-strength {
      height: 4px;
      border-radius: 2px;
      background: var(--color-border);
      margin-top: 4px;
      overflow: hidden;
    }
    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: width 0.3s ease;
      background: var(--color-danger);
    }
    .password-strength-bar.weak { 
      width: 25%; 
      background: var(--color-danger); 
    }
    .password-strength-bar.fair { 
      width: 50%; 
      background: #ff9800; 
    }
    .password-strength-bar.good { 
      width: 75%; 
      background: #4caf50; 
    }
    .password-strength-bar.strong { 
      width: 100%; 
      background: var(--color-success); 
    }
    .password-input-wrapper {
      position: relative;
    }
    .password-toggle {
      position: absolute;
      right: var(--space-sm);
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: var(--color-text-secondary);
      cursor: pointer;
      padding: var(--space-xs);
    }
    .password-toggle:hover {
      color: var(--color-primary);
    }
    .terms-checkbox {
      margin: var(--space-md) 0;
      font-size: 0.875rem;
    }
    .terms-checkbox input {
      margin-right: 8px;
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 280px; height: 320px; border-radius: var(--radius-xl);"></div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="index.php">Home</a>
          <a href="login.php">Login</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="index.php" class="btn btn-ghost">Home</a>
        <a href="login.php" class="btn btn-primary">Login</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php">Home</a>
    <a href="login.php">Login</a>
  </nav>

  <main class="auth-page">
    <div class="card auth-card">
      <h1>Create account</h1>
      <p class="subtitle">Join CareerGuide and start your career assessment.</p>
      
      <?php if (isset($errors['database'])): ?>
        <div class="alert alert-danger">
          <?php echo htmlspecialchars($errors['database']); ?>
        </div>
      <?php endif; ?>
      
      <form id="registerForm" method="post" action="">
        <div class="form-group">
          <label class="form-label" for="name">Full name</label>
          <input type="text" 
                 id="name" 
                 name="name" 
                 class="form-input <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                 placeholder="Your name" 
                 required 
                 autocomplete="name"
                 value="<?php echo isset($old_data['name']) ? htmlspecialchars($old_data['name']) : ''; ?>">
          <?php if (isset($errors['name'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['name']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" 
                 id="email" 
                 name="email" 
                 class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                 placeholder="you@example.com" 
                 required 
                 autocomplete="email"
                 value="<?php echo isset($old_data['email']) ? htmlspecialchars($old_data['email']) : ''; ?>">
          <?php if (isset($errors['email'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['email']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-input-wrapper">
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                   placeholder="Min 8 characters" 
                   required 
                   minlength="8" 
                   autocomplete="new-password">
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
              <svg class="icon-eye" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="passwordStrengthBar"></div>
          </div>
          <div class="password-requirements">
            Password must contain: 8+ characters, uppercase, lowercase, number
          </div>
          <?php if (isset($errors['password'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['password']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="confirmPassword">Confirm password</label>
          <div class="password-input-wrapper">
            <input type="password" 
                   id="confirmPassword" 
                   name="confirmPassword" 
                   class="form-input <?php echo isset($errors['confirm']) ? 'error' : ''; ?>" 
                   placeholder="••••••••" 
                   required 
                   autocomplete="new-password">
            <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Show password">
              <svg class="icon-eye" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <?php if (isset($errors['confirm'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['confirm']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="terms-checkbox">
          <label>
            <input type="checkbox" name="terms" id="terms" required>
            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
          </label>
          <span class="form-error" id="termsError"></span>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Create account</button>
      </form>
      
      <div class="auth-links">
        <a href="login.php">Already have an account? Sign in</a>
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
      
      // Hide page loader
      var loader = document.getElementById('pageLoader');
      if (loader) {
        window.addEventListener('load', function() { 
          setTimeout(function() { 
            loader.classList.add('hidden'); 
          }, 300); 
        });
      }

      // Password visibility toggles
      function setupPasswordToggle(inputId, toggleId) {
        var toggle = document.getElementById(toggleId);
        var input = document.getElementById(inputId);
        if (toggle && input) {
          toggle.addEventListener('click', function() {
            var type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            var icon = this.querySelector('.icon-eye');
            if (type === 'text') {
              icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
              icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
            this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
          });
        }
      }
      
      setupPasswordToggle('password', 'passwordToggle');
      setupPasswordToggle('confirmPassword', 'confirmPasswordToggle');

      // Password strength checker
      var passwordInput = document.getElementById('password');
      var strengthBar = document.getElementById('passwordStrengthBar');
      
      function checkPasswordStrength(password) {
        var strength = 0;
        var criteria = {
          length: password.length >= 8,
          lower: /[a-z]/.test(password),
          upper: /[A-Z]/.test(password),
          number: /[0-9]/.test(password),
          special: /[^A-Za-z0-9]/.test(password)
        };
        
        strength += criteria.length ? 1 : 0;
        strength += criteria.lower ? 1 : 0;
        strength += criteria.upper ? 1 : 0;
        strength += criteria.number ? 1 : 0;
        strength += criteria.special ? 1 : 0;
        
        // Update strength bar
        strengthBar.className = 'password-strength-bar';
        if (password.length === 0) {
          strengthBar.style.width = '0%';
        } else if (strength <= 1) {
          strengthBar.className += ' weak';
        } else if (strength <= 2) {
          strengthBar.className += ' fair';
        } else if (strength <= 3) {
          strengthBar.className += ' good';
        } else {
          strengthBar.className += ' strong';
        }
      }
      
      if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
          checkPasswordStrength(this.value);
        });
      }

      // Password confirmation check
      var confirmInput = document.getElementById('confirmPassword');
      if (passwordInput && confirmInput) {
        confirmInput.addEventListener('input', function() {
          if (this.value && passwordInput.value && this.value !== passwordInput.value) {
            this.classList.add('error');
          } else {
            this.classList.remove('error');
          }
        });
      }

      // Form validation
      var form = document.getElementById('registerForm');
      var submitBtn = document.getElementById('submitBtn');
      
      form.addEventListener('submit', function(e) {
        // Clear previous error styling
        document.querySelectorAll('.form-input').forEach(function(input) {
          input.classList.remove('error');
        });
        
        var name = document.getElementById('name').value.trim();
        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        var confirm = document.getElementById('confirmPassword').value;
        var terms = document.getElementById('terms').checked;
        
        var hasError = false;
        
        if (!name || name.length < 2) {
          document.getElementById('name').classList.add('error');
          hasError = true;
        }
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          document.getElementById('email').classList.add('error');
          hasError = true;
        }
        
        if (!password || password.length < 8) {
          document.getElementById('password').classList.add('error');
          hasError = true;
        }
        
        if (!confirm || password !== confirm) {
          document.getElementById('confirmPassword').classList.add('error');
          hasError = true;
        }
        
        if (!terms) {
          document.getElementById('termsError').textContent = 'You must accept the terms and conditions';
          hasError = true;
        } else {
          document.getElementById('termsError').textContent = '';
        }
        
        if (hasError) {
          e.preventDefault();
          return false;
        }
        
        // If no errors, submit form
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Creating account...';
          submitBtn.classList.add('loading');
        }
        
        return true;
      });

      // Auto-focus name field
      document.getElementById('name')?.focus();

    })();
  </script>
</body>
</html>