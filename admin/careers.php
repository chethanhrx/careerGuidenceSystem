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

// Add/Edit Career
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $slug = mysqli_real_escape_string($conn, trim($_POST['slug']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $overview = mysqli_real_escape_string($conn, trim($_POST['overview']));
    $salary = mysqli_real_escape_string($conn, trim($_POST['salary']));
    
    // Process skills (comma-separated to JSON)
    $skills = isset($_POST['skills']) ? $_POST['skills'] : '';
    $skills_array = array_map('trim', explode(',', $skills));
    $skills_json = json_encode($skills_array);
    
    // Process roles (comma-separated to JSON)
    $roles = isset($_POST['roles']) ? $_POST['roles'] : '';
    $roles_array = array_map('trim', explode(',', $roles));
    $roles_json = json_encode($roles_array);
    
    // Process roadmap (comma-separated to JSON)
    $roadmap = isset($_POST['roadmap']) ? $_POST['roadmap'] : '';
    $roadmap_array = array_map('trim', explode(',', $roadmap));
    $roadmap_json = json_encode($roadmap_array);
    
    // Process courses (comma-separated to JSON)
    $courses = isset($_POST['courses']) ? $_POST['courses'] : '';
    $courses_array = array_map('trim', explode(',', $courses));
    $courses_json = json_encode($courses_array);
    
    // Process certifications (comma-separated to JSON)
    $certs = isset($_POST['certs']) ? $_POST['certs'] : '';
    $certs_array = array_map('trim', explode(',', $certs));
    $certs_json = json_encode($certs_array);
    
    if (empty($title) || empty($slug)) {
        $message = 'Title and slug are required';
        $message_type = 'error';
    } else {
        if ($id > 0) {
            // Update existing career - using your column names
            $sql = "UPDATE careers SET 
                    title = '$title',
                    slug = '$slug',
                    category = '$category',
                    overview = '$overview',
                    skills = '$skills_json',
                    salary = '$salary',
                    roles = '$roles_json',
                    roadmap = '$roadmap_json',
                    courses = '$courses_json',
                    certs = '$certs_json',
                    updated_at = NOW()
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: careers.php?success=updated");
                exit();
            } else {
                $message = 'Error updating career: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        } else {
            // Insert new career - using your column names
            $sql = "INSERT INTO careers (title, slug, category, overview, skills, salary, roles, roadmap, courses, certs, created_at) 
                    VALUES ('$title', '$slug', '$category', '$overview', '$skills_json', '$salary', '$roles_json', '$roadmap_json', '$courses_json', '$certs_json', NOW())";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: careers.php?success=added");
                exit();
            } else {
                $message = 'Error adding career: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Handle success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $message = 'Career added successfully';
        $message_type = 'success';
    } elseif ($_GET['success'] == 'updated') {
        $message = 'Career updated successfully';
        $message_type = 'success';
    } elseif ($_GET['success'] == 'deleted') {
        $message = 'Career deleted successfully';
        $message_type = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $sql = "DELETE FROM careers WHERE id = $delete_id";
    if (mysqli_query($conn, $sql)) {
        header("Location: careers.php?success=deleted");
        exit();
    } else {
        $message = 'Error deleting career: ' . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Get all careers
$sql = "SELECT * FROM careers ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$careers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode JSON fields
        $row['skills'] = json_decode($row['skills'] ?? '[]', true) ?: [];
        $row['roles'] = json_decode($row['roles'] ?? '[]', true) ?: [];
        $row['roadmap'] = json_decode($row['roadmap'] ?? '[]', true) ?: [];
        $row['courses'] = json_decode($row['courses'] ?? '[]', true) ?: [];
        $row['certs'] = json_decode($row['certs'] ?? '[]', true) ?: [];
        $careers[] = $row;
    }
}

// Get career for editing
$edit_career = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM careers WHERE id = $edit_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_career = mysqli_fetch_assoc($result);
        $edit_career['skills'] = json_decode($edit_career['skills'] ?? '[]', true) ?: [];
        $edit_career['roles'] = json_decode($edit_career['roles'] ?? '[]', true) ?: [];
        $edit_career['roadmap'] = json_decode($edit_career['roadmap'] ?? '[]', true) ?: [];
        $edit_career['courses'] = json_decode($edit_career['courses'] ?? '[]', true) ?: [];
        $edit_career['certs'] = json_decode($edit_career['certs'] ?? '[]', true) ?: [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Careers | CareerGuide Admin</title>
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
    .career-list { 
      display: grid; 
      gap: var(--space-md); 
      margin-top: var(--space-lg);
    }
    .career-item { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      padding: var(--space-lg); 
      border: 1px solid var(--color-border); 
      border-radius: var(--radius-lg); 
      flex-wrap: wrap; 
      gap: var(--space-md);
      background: var(--color-bg);
      transition: all 0.2s ease;
    }
    .career-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    .career-item-content {
      flex: 1;
      min-width: 300px;
    }
    .career-item h3 { 
      font-size: var(--text-base); 
      margin-bottom: var(--space-xs); 
      color: var(--color-text-primary);
    }
    .career-item p { 
      font-size: var(--text-sm); 
      color: var(--color-text-secondary);
      margin-bottom: var(--space-sm);
      line-height: 1.5;
    }
    .career-meta {
      display: flex;
      flex-wrap: wrap;
      gap: var(--space-md);
      font-size: var(--text-xs);
      color: var(--color-text-muted);
    }
    .career-actions { 
      display: flex; 
      gap: var(--space-sm); 
      flex-wrap: wrap;
    }
    .modal { 
      position: fixed; 
      inset: 0; 
      background: rgba(0,0,0,0.5); 
      z-index: 1000; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      padding: var(--space-lg); 
      opacity: 0; 
      visibility: hidden; 
      transition: opacity 0.3s ease, visibility 0.3s ease; 
    }
    .modal.open { 
      opacity: 1; 
      visibility: visible; 
    }
    .modal-content { 
      background: var(--color-bg); 
      border-radius: var(--radius-xl); 
      padding: var(--space-xl); 
      width: 100%; 
      max-width: 800px; 
      max-height: 90vh; 
      overflow-y: auto; 
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    .modal-content h2 { 
      margin-bottom: var(--space-lg); 
      color: var(--color-text-primary);
    }
    .modal-actions { 
      margin-top: var(--space-lg); 
      display: flex; 
      gap: var(--space-md); 
      justify-content: flex-end; 
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
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: var(--space-md);
    }
    .form-tab {
      background: var(--color-bg-light);
      padding: var(--space-sm) var(--space-md);
      border-radius: var(--radius-md);
      margin-right: var(--space-sm);
      cursor: pointer;
      border: 1px solid transparent;
    }
    .form-tab.active {
      background: var(--color-primary);
      color: white;
      border-color: var(--color-primary);
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }
    .add-career-btn {
      display: flex;
      align-items: center;
      gap: 8px;
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="skeleton" style="width: 90%; max-width: 600px; height: 200px; border-radius: var(--radius-xl);"></div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span> Admin</a>
        <nav class="nav-links">
          <a href="index.php">Dashboard</a>
          <a href="careers.php" class="active">Careers</a>
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
    <a href="careers.php" class="active">Careers</a>
    <a href="questions.php">Questions</a>
    <a href="users.php">Users</a>
    <a href="../index.php">View Site</a>
    <a href="logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1>Manage Careers</h1>
    <button type="button" class="btn btn-primary add-career-btn" id="addCareerBtn" onclick="openModal()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add New Career
    </button>
  </div>

  <main class="admin-main">
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($careers)): ?>
      <div class="empty-state">
        <h3>No careers found</h3>
        <p>Start by adding your first career option.</p>
        <button type="button" class="btn btn-primary" onclick="openModal()" style="margin-top: var(--space-md);">
          Add First Career
        </button>
      </div>
    <?php else: ?>
      <div class="career-list" id="careerList">
        <?php foreach ($careers as $career): ?>
          <div class="career-item card">
            <div class="career-item-content">
              <h3><?php echo htmlspecialchars($career['title']); ?></h3>
              <?php if (!empty($career['category'])): ?>
                <span style="font-size: var(--text-xs); padding: 2px 8px; background: var(--color-secondary-light); color: var(--color-secondary); border-radius: 12px; margin-bottom: var(--space-sm); display: inline-block;">
                  <?php echo htmlspecialchars($career['category']); ?>
                </span>
              <?php endif; ?>
              <p><?php echo htmlspecialchars(substr($career['overview'] ?? '', 0, 150) . '...'); ?></p>
              
              <?php if (!empty($career['skills'])): ?>
                <div style="margin-bottom: var(--space-sm);">
                  <?php foreach (array_slice($career['skills'], 0, 5) as $skill): ?>
                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                  <?php endforeach; ?>
                  <?php if (count($career['skills']) > 5): ?>
                    <span class="skill-tag">+<?php echo count($career['skills']) - 5; ?> more</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <div class="career-meta">
                <span>Slug: <?php echo htmlspecialchars($career['slug']); ?></span>
                <?php if (!empty($career['salary'])): ?>
                  <span>Salary: <?php echo htmlspecialchars($career['salary']); ?></span>
                <?php endif; ?>
                <span>Created: <?php echo date('d M Y', strtotime($career['created_at'])); ?></span>
              </div>
            </div>
            
            <div class="career-actions">
              <a href="?edit=<?php echo $career['id']; ?>" class="btn btn-secondary">
                Edit
              </a>
              <a href="?delete=<?php echo $career['id']; ?>" 
                 class="btn btn-ghost" 
                 onclick="return confirm('Are you sure you want to delete this career? This action cannot be undone.')">
                Delete
              </a>
              <a href="../career-details.php?slug=<?php echo urlencode($career['slug']); ?>" 
                 target="_blank" 
                 class="btn btn-outline">
                View
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Career Modal -->
  <div class="modal <?php echo $edit_career ? 'open' : ''; ?>" id="careerModal">
    <div class="modal-content">
      <h2 id="modalTitle"><?php echo $edit_career ? 'Edit Career' : 'Add New Career'; ?></h2>
      
      <!-- Tab Navigation -->
      <div style="display: flex; margin-bottom: var(--space-lg); border-bottom: 1px solid var(--color-border); padding-bottom: var(--space-sm);">
        <div class="form-tab active" onclick="switchTab('basic')">Basic Info</div>
        <div class="form-tab" onclick="switchTab('skills')">Skills & Roles</div>
        <div class="form-tab" onclick="switchTab('additional')">Additional Info</div>
      </div>
      
      <form id="careerForm" method="post" action="">
        <input type="hidden" id="careerId" name="id" value="<?php echo $edit_career['id'] ?? ''; ?>">
        
        <!-- Basic Info Tab -->
        <div class="tab-content active" id="basicTab">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="careerTitle">Career Title *</label>
              <input type="text" 
                     id="careerTitle" 
                     name="title" 
                     class="form-input" 
                     placeholder="e.g. Software Developer" 
                     required
                     value="<?php echo htmlspecialchars($edit_career['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
              <label class="form-label" for="careerSlug">URL Slug *</label>
              <input type="text" 
                     id="careerSlug" 
                     name="slug" 
                     class="form-input" 
                     placeholder="e.g. software-developer" 
                     required
                     value="<?php echo htmlspecialchars($edit_career['slug'] ?? ''); ?>">
              <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Used in URLs (lowercase, hyphens)</small>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="careerCategory">Category</label>
              <input type="text" 
                     id="careerCategory" 
                     name="category" 
                     class="form-input" 
                     placeholder="e.g. Technology, Design, Marketing"
                     value="<?php echo htmlspecialchars($edit_career['category'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
              <label class="form-label" for="careerSalary">Salary Range</label>
              <input type="text" 
                     id="careerSalary" 
                     name="salary" 
                     class="form-input" 
                     placeholder="e.g. ₹4-25 LPA"
                     value="<?php echo htmlspecialchars($edit_career['salary'] ?? ''); ?>">
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="careerOverview">Career Overview</label>
            <textarea id="careerOverview" 
                      name="overview" 
                      class="form-textarea" 
                      placeholder="Describe this career, its importance, and what it involves..." 
                      rows="4"><?php echo htmlspecialchars($edit_career['overview'] ?? ''); ?></textarea>
          </div>
        </div>
        
        <!-- Skills & Roles Tab -->
        <div class="tab-content" id="skillsTab">
          <div class="form-group">
            <label class="form-label" for="careerSkills">Required Skills (comma-separated)</label>
            <textarea id="careerSkills" 
                      name="skills" 
                      class="form-textarea" 
                      placeholder="e.g. Programming, Problem Solving, Teamwork, Communication, Project Management" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_career['skills'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Separate skills with commas</small>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="careerRoles">Job Roles (comma-separated)</label>
            <textarea id="careerRoles" 
                      name="roles" 
                      class="form-textarea" 
                      placeholder="e.g. Frontend Developer, Backend Developer, Full Stack Developer, Mobile Developer" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_career['roles'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Different job titles/positions in this career</small>
          </div>
        </div>
        
        <!-- Additional Info Tab -->
        <div class="tab-content" id="additionalTab">
          <div class="form-group">
            <label class="form-label" for="careerRoadmap">Learning Roadmap (comma-separated)</label>
            <textarea id="careerRoadmap" 
                      name="roadmap" 
                      class="form-textarea" 
                      placeholder="e.g. Learn basics, Build projects, Learn frameworks, Get certified, Apply for jobs" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_career['roadmap'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Steps to learn this career</small>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="careerCourses">Suggested Courses (comma-separated)</label>
            <textarea id="careerCourses" 
                      name="courses" 
                      class="form-textarea" 
                      placeholder="e.g. CS50 Harvard, Full Stack Open, Google IT Certificate" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_career['courses'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Recommended courses or platforms</small>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="careerCerts">Certifications (comma-separated)</label>
            <textarea id="careerCerts" 
                      name="certs" 
                      class="form-textarea" 
                      placeholder="e.g. AWS Certified Developer, Google Data Analytics, PMP" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_career['certs'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Industry-recognized certifications</small>
          </div>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary"><?php echo $edit_career ? 'Update Career' : 'Add Career'; ?></button>
        </div>
      </form>
    </div>
  </div>

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

  // Initialize tabs
  var tabButtons = document.querySelectorAll('.form-tab');
  tabButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      var tabName = this.textContent.toLowerCase();
      if (tabName.includes('basic')) switchTab('basic');
      else if (tabName.includes('skills')) switchTab('skills');
      else if (tabName.includes('additional')) switchTab('additional');
    });
  });

  // Auto-generate slug from title
  var titleInput = document.getElementById('careerTitle');
  var slugInput = document.getElementById('careerSlug');
  if (titleInput && slugInput) {
    titleInput.addEventListener('blur', function() {
      if (!slugInput.value || slugInput.value === '') {
        var slug = this.value
          .toLowerCase()
          .replace(/[^\w\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-');
        slugInput.value = slug;
      }
    });
  }

  // Add Career button event listener
  var addCareerBtn = document.getElementById('addCareerBtn');
  if (addCareerBtn) {
    addCareerBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openModal();
    });
  }

  // Also add click event to the "Add First Career" button
  var addFirstCareerBtn = document.querySelector('.empty-state .btn');
  if (addFirstCareerBtn && addFirstCareerBtn.textContent.includes('Add First Career')) {
    addFirstCareerBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openModal();
    });
  }

  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('careerModal').classList.contains('open')) {
      closeModal();
    }
  });

  // Close modal when clicking outside
  var careerModal = document.getElementById('careerModal');
  if (careerModal) {
    careerModal.addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });
  }

  // Auto-open modal if editing (from PHP)
  <?php if ($edit_career): ?>
  // Set edit mode
  document.getElementById('modalTitle').textContent = 'Edit Career';
  document.getElementById('careerId').value = '<?php echo $edit_career['id']; ?>';
  // Show modal
  document.getElementById('careerModal').classList.add('open');
  <?php endif; ?>
});

// Global functions for modal (must be outside DOMContentLoaded for onclick to work)
function openModal() {
  // Reset form and modal title for new career
  document.getElementById('modalTitle').textContent = 'Add New Career';
  document.getElementById('careerForm').reset();
  document.getElementById('careerId').value = '';
  // Reset to basic tab
  switchTab('basic');
  // Show modal
  document.getElementById('careerModal').classList.add('open');
}

function closeModal() {
  document.getElementById('careerModal').classList.remove('open');
}

function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(function(tab) {
    tab.classList.remove('active');
  });
  
  // Remove active class from all tab buttons
  document.querySelectorAll('.form-tab').forEach(function(btn) {
    btn.classList.remove('active');
  });
  
  // Show selected tab
  var tabElement = document.getElementById(tabName + 'Tab');
  if (tabElement) {
    tabElement.classList.add('active');
  }
  
  // Find and activate tab button
  var tabButtons = document.querySelectorAll('.form-tab');
  tabButtons.forEach(function(btn) {
    if (btn.textContent.toLowerCase().includes(tabName)) {
      btn.classList.add('active');
    }
  });
}
</script>
</body>
</html>