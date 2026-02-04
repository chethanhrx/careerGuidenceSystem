<?php
session_start();
require_once '../config.php';

// Check if admin is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: index.php");
    exit();
}

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
        // Query to check user - must be admin (is_admin = 1)
        $sql = "SELECT id, name, email, password_hash, is_admin FROM users WHERE email = '$email' AND is_admin = 1";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = '{$user['id']}'";
                mysqli_query($conn, $update_sql);
                
                // Redirect to admin dashboard
                header("Location: index.php");
                exit();
            } else {
                $errors['password'] = "Invalid email or password";
            }
        } else {
            $errors['email'] = "Invalid email or not an admin";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | CareerGuide</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .auth-page { 
      min-height: 100vh; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      padding: var(--space-xl);
      background: var(--color-bg-light);
    }
    .auth-card { 
      width: 100%; 
      max-width: 400px; 
      padding: var(--space-2xl);
      background: var(--color-bg);
      border-radius: var(--radius-xl);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }
    .auth-card h1 { 
      margin-bottom: var(--space-sm); 
      text-align: center;
      color: var(--color-primary);
    }
    .auth-card .subtitle { 
      color: var(--color-text-secondary); 
      margin-bottom: var(--space-xl);
      text-align: center;
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
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }
    .theme-toggle-container {
      position: fixed;
      top: var(--space-md);
      right: var(--space-md);
      z-index: 10;
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 280px; height: 200px; border-radius: var(--radius-xl);"></div>
  </div>

  <div class="theme-toggle-container">
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
      <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
    </button>
  </div>

  <main class="auth-page">
    <div class="card auth-card">
      <h1>Admin Login</h1>
      <p class="subtitle">Sign in to manage careers and users.</p>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <strong>Login failed:</strong> Please check your credentials.
        </div>
      <?php endif; ?>
      
      <form id="adminLoginForm" method="post" action="">
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" 
                 id="email" 
                 name="email" 
                 class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                 placeholder="admin@example.com" 
                 required
                 value="<?php echo isset($old_data['email']) ? htmlspecialchars($old_data['email']) : ''; ?>">
          <?php if (isset($errors['email'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['email']); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" 
                 id="password" 
                 name="password" 
                 class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                 placeholder="••••••••" 
                 required>
          <?php if (isset($errors['password'])): ?>
            <span class="form-error"><?php echo htmlspecialchars($errors['password']); ?></span>
          <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Sign In</button>
      </form>
      
      <div class="auth-links">
        <a href="../index.php">Back to site</a>
        <a href="../login.php">User login</a>
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
      
      // Hide page loader
      var loader = document.getElementById('pageLoader');
      if (loader) {
        window.addEventListener('load', function() { 
          setTimeout(function() { 
            loader.classList.add('hidden'); 
          }, 300); 
        });
      }

      // Form validation
      var form = document.getElementById('adminLoginForm');
      var submitBtn = document.getElementById('submitBtn');
      
      form.addEventListener('submit', function(e) {
        // Clear previous error styling
        document.querySelectorAll('.form-input').forEach(function(input) {
          input.classList.remove('error');
        });
        
        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        
        var hasError = false;
        
        if (!email) {
          document.getElementById('email').classList.add('error');
          hasError = true;
        }
        
        if (!password) {
          document.getElementById('password').classList.add('error');
          hasError = true;
        }
        
        if (!hasError && submitBtn) {
          // Disable button and show loading state
          submitBtn.disabled = true;
          submitBtn.textContent = 'Signing in...';
          submitBtn.classList.add('loading');
          
          // Form will submit normally to PHP backend
          return true;
        } else if (hasError) {
          e.preventDefault();
          return false;
        }
      });

      // Auto-focus email field
      document.getElementById('email')?.focus();

      // Handle "Enter" key for login
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
          var activeElement = document.activeElement;
          if (activeElement.tagName === 'INPUT' && activeElement.type !== 'submit') {
            e.preventDefault();
            form.requestSubmit();
          }
        }
      });

    })();
  </script>
</body>
</html>