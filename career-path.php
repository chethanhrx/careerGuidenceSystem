<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get career slug from query string
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: test-results.php");
    exit();
}

// Fetch the career
$stmt = $conn->prepare("SELECT * FROM careers WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    header("Location: test-results.php");
    exit();
}
$career = $result->fetch_assoc();
$stmt->close();

$career_id = $career['id'];

// Decode existing JSON fields
$career_skills  = json_decode($career['skills']  ?? '[]', true) ?: [];
$career_roadmap = json_decode($career['roadmap'] ?? '[]', true) ?: [];
$career_roles   = json_decode($career['roles']   ?? '[]', true) ?: [];
$career_certs   = json_decode($career['certs']   ?? '[]', true) ?: [];

// Fetch courses from dedicated table
$courses_stmt = $conn->prepare("SELECT * FROM courses WHERE career_id = ? ORDER BY course_level, is_free DESC, rating DESC");
$courses_stmt->bind_param("i", $career_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}
$courses_stmt->close();

// Fetch colleges from dedicated table
$colleges_stmt = $conn->prepare("SELECT * FROM colleges WHERE career_id = ? ORDER BY type, name ASC");
$colleges_stmt->bind_param("i", $career_id);
$colleges_stmt->execute();
$colleges_result = $colleges_stmt->get_result();
$colleges = [];
while ($row = $colleges_result->fetch_assoc()) {
    $colleges[] = $row;
}
$colleges_stmt->close();

// Fetch user's saved resources so we can pre-highlight saved items
$saved_courses  = [];
$saved_colleges = [];
$saved_stmt = $conn->prepare("SELECT resource_type, resource_id FROM user_saved_resources WHERE user_id = ?");
$saved_stmt->bind_param("i", $user_id);
$saved_stmt->execute();
$saved_result = $saved_stmt->get_result();
while ($row = $saved_result->fetch_assoc()) {
    if ($row['resource_type'] === 'course')  $saved_courses[]  = $row['resource_id'];
    if ($row['resource_type'] === 'college') $saved_colleges[] = $row['resource_id'];
}
$saved_stmt->close();

// Check if THIS career is saved
$is_career_saved = false;
$saved_career_stmt = $conn->prepare("SELECT id FROM user_careers WHERE user_id = ? AND career_id = ?");
$saved_career_stmt->bind_param("ii", $user_id, $career_id);
$saved_career_stmt->execute();
$is_career_saved = $saved_career_stmt->get_result()->num_rows > 0;
$saved_career_stmt->close();

// Get user's match score for this career from latest test
$match_score = 0;
$score_stmt = $conn->prepare("SELECT results FROM user_tests WHERE user_id = ? AND test_type = 'career_assessment' ORDER BY completed_at DESC LIMIT 1");
$score_stmt->bind_param("i", $user_id);
$score_stmt->execute();
$score_result = $score_stmt->get_result();
if ($score_result && $score_result->num_rows > 0) {
    $score_row  = $score_result->fetch_assoc();
    $score_data = json_decode($score_row['results'], true);
    $match_score= (int)($score_data['scores'][$slug] ?? 0);
}
$score_stmt->close();

$csrf_token = generateCSRFToken();

// ── Roadmap: build structured stages from careers.roadmap JSON ──
// Expected format: [{title, desc}, ...] (4 items: beginner→job ready)
$roadmap_stages = [];
$stage_labels = ['Beginner', 'Intermediate', 'Advanced', 'Job Ready'];
$stage_icons  = ['🌱', '📚', '🚀', '💼'];
$stage_colors = ['#2563eb', '#7c3aed', '#059669', '#d97706'];
foreach ($career_roadmap as $i => $step) {
    $roadmap_stages[] = [
        'label' => $stage_labels[$i] ?? 'Step ' . ($i + 1),
        'icon'  => $stage_icons[$i]  ?? '📌',
        'color' => $stage_colors[$i] ?? '#2563eb',
        'title' => is_array($step) ? ($step['title'] ?? '') : $step,
        'desc'  => is_array($step) ? ($step['desc']  ?? '') : '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Path: <?php echo htmlspecialchars($career['title']); ?> | CareerGuide</title>
  <meta name="description" content="Your complete career path guide for <?php echo htmlspecialchars($career['title']); ?> — courses, colleges, and a step-by-step roadmap.">
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ── Career Path Page Styles ── */
    .cp-page { max-width: 1200px; margin: 0 auto; padding: var(--space-lg) var(--space-md); }

    /* Hero */
    .cp-hero {
      background: var(--color-bg-card);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-xl);
      padding: var(--space-2xl) var(--space-xl);
      margin-bottom: var(--space-xl);
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-md);
    }
    .cp-hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--color-primary), #7c3aed, #059669);
    }
    .cp-hero-inner { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: var(--space-lg); }
    .cp-hero-title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; margin-bottom: var(--space-sm); }
    .cp-hero-sub   { color: var(--color-text-secondary); max-width: 600px; line-height: 1.6; }
    .cp-hero-badges { display: flex; flex-wrap: wrap; gap: var(--space-sm); margin-top: var(--space-md); }
    .cp-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: var(--radius-full);
      font-size: var(--text-sm); font-weight: 600;
    }
    .cp-badge-match  { background: var(--color-primary-light); color: var(--color-primary); }
    .cp-badge-salary { background: var(--color-success-bg, #d1fae5); color: var(--color-success); }
    .cp-badge-cat    { background: var(--color-surface); color: var(--color-text-secondary); }
    .cp-match-ring {
      width: 110px; height: 110px; flex-shrink: 0;
      border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;
      background: conic-gradient(var(--color-primary) calc(<?php echo $match_score; ?>% * 3.6deg), var(--color-surface) 0);
      box-shadow: var(--shadow-md);
      position: relative;
    }
    .cp-match-ring::after {
      content: '';
      position: absolute; inset: 12px;
      background: var(--color-bg-card);
      border-radius: 50%;
    }
    .cp-match-ring-inner { position: relative; z-index: 1; text-align: center; }
    .cp-match-ring-pct   { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); }
    .cp-match-ring-lbl   { font-size: 0.65rem; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }

    /* Tabs */
    .cp-tabs { display: flex; gap: var(--space-xs); margin-bottom: var(--space-xl); border-bottom: 1px solid var(--color-border); padding-bottom: 0; overflow-x: auto; }
    .cp-tab {
      padding: var(--space-md) var(--space-lg);
      font-size: var(--text-sm); font-weight: 600;
      color: var(--color-text-secondary);
      border-bottom: 3px solid transparent;
      margin-bottom: -1px;
      cursor: pointer;
      white-space: nowrap;
      transition: all var(--transition-fast);
    }
    .cp-tab:hover  { color: var(--color-primary); }
    .cp-tab.active { color: var(--color-primary); border-bottom-color: var(--color-primary); }
    .cp-tab-emoji  { margin-right: 6px; }
    .cp-panel      { display: none; }
    .cp-panel.active { display: block; animation: fadeInUp .3s ease; }
    @keyframes fadeInUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    /* Filter bar */
    .cp-filter-bar { display: flex; flex-wrap: wrap; gap: var(--space-sm); margin-bottom: var(--space-lg); align-items: center; }
    .cp-filter-label { font-size: var(--text-sm); font-weight: 600; color: var(--color-text-secondary); }
    .cp-filter-btn {
      padding: 6px 14px; border-radius: var(--radius-full); border: 1px solid var(--color-border);
      font-size: var(--text-sm); font-weight: 500; cursor: pointer;
      color: var(--color-text-secondary); background: var(--color-bg-card);
      transition: all var(--transition-fast);
    }
    .cp-filter-btn.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
    .cp-filter-btn:hover:not(.active) { border-color: var(--color-primary); color: var(--color-primary); }

    /* Resource grid */
    .cp-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
      gap: var(--space-lg);
    }

    /* Course card */
    .cp-course-card {
      background: var(--color-bg-card);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-xl);
      padding: var(--space-lg);
      display: flex; flex-direction: column;
      box-shadow: var(--shadow-sm);
      transition: transform var(--transition-fast), box-shadow var(--transition-fast);
      position: relative;
    }
    .cp-course-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

    .cp-course-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-sm); gap: var(--space-sm); }
    .cp-course-platform {
      font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
      padding: 4px 10px; border-radius: var(--radius-full);
      background: var(--color-primary-light); color: var(--color-primary);
    }
    .cp-course-title { font-size: var(--text-base); font-weight: 700; margin-bottom: var(--space-xs); color: var(--color-text); line-height: 1.3; }
    .cp-course-desc  { font-size: var(--text-sm); color: var(--color-text-secondary); line-height: 1.5; flex: 1; margin-bottom: var(--space-md); }
    .cp-course-meta  { display: flex; flex-wrap: wrap; gap: var(--space-sm); align-items: center; margin-bottom: var(--space-md); }
    .cp-meta-pill {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: var(--text-xs); padding: 3px 10px; border-radius: var(--radius-full);
      background: var(--color-surface); color: var(--color-text-secondary);
    }
    .cp-meta-pill.free  { background: var(--color-success-bg, #d1fae5); color: var(--color-success); }
    .cp-meta-pill.paid  { background: var(--color-warning-bg, #fef3c7); color: var(--color-warning); }
    .cp-meta-pill.beg   { background: #dbeafe; color: #1d4ed8; }
    .cp-meta-pill.inter { background: #ede9fe; color: #7c3aed; }
    .cp-meta-pill.adv   { background: #fce7f3; color: #be185d; }
    .cp-rating { display: flex; align-items: center; gap: 4px; font-size: var(--text-sm); font-weight: 700; color: var(--color-warning); }
    .cp-course-footer { display: flex; gap: var(--space-sm); align-items: center; margin-top: auto; }
    .cp-link-btn {
      flex: 1; text-align: center; padding: 10px; border-radius: var(--radius-lg);
      background: var(--color-primary); color: #fff; font-size: var(--text-sm); font-weight: 600;
      transition: all var(--transition-fast);
    }
    .cp-link-btn:hover { background: var(--color-primary-hover, #1d4ed8); color: #fff; transform: translateY(-1px); box-shadow: var(--shadow-md); }

    /* Save button */
    .cp-save-btn {
      width: 40px; height: 40px; border-radius: var(--radius-lg); flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      border: 1px solid var(--color-border); background: var(--color-bg-card);
      cursor: pointer; transition: all var(--transition-fast); font-size: 1.1rem;
    }
    .cp-save-btn:hover           { border-color: #ef4444; background: #fee2e2; }
    .cp-save-btn.saved           { border-color: #ef4444; background: #fee2e2; color: #ef4444; }
    .cp-save-btn.saving          { opacity: .6; pointer-events: none; }

    /* College card */
    .cp-college-card {
      background: var(--color-bg-card);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-xl);
      padding: var(--space-lg);
      display: flex; flex-direction: column;
      box-shadow: var(--shadow-sm);
      transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }
    .cp-college-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .cp-college-header { margin-bottom: var(--space-md); }
    .cp-college-badges { display: flex; flex-wrap: wrap; gap: var(--space-xs); margin-bottom: var(--space-sm); }
    .cp-type-badge {
      font-size: var(--text-xs); font-weight: 700; padding: 3px 10px; border-radius: var(--radius-full);
      text-transform: capitalize;
    }
    .cp-type-college       { background: #dbeafe; color: #1d4ed8; }
    .cp-type-institute     { background: #ede9fe; color: #7c3aed; }
    .cp-type-training_center { background: #fce7f3; color: #be185d; }
    .cp-type-bootcamp      { background: #d1fae5; color: #059669; }
    .cp-mode-badge { font-size: var(--text-xs); padding: 3px 10px; border-radius: var(--radius-full); font-weight: 600; }
    .cp-mode-online  { background: var(--color-success-bg,#d1fae5); color: var(--color-success); }
    .cp-mode-offline { background: var(--color-warning-bg,#fef3c7); color: var(--color-warning); }
    .cp-mode-hybrid  { background: #e0f2fe; color: #0284c7; }
    .cp-college-name { font-size: var(--text-base); font-weight: 700; color: var(--color-text); margin-bottom: 4px; }
    .cp-college-location { font-size: var(--text-sm); color: var(--color-text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: var(--space-sm); }
    .cp-college-course { font-size: var(--text-sm); color: var(--color-text-secondary); font-weight: 500; margin-bottom: var(--space-sm); }
    .cp-college-desc { font-size: var(--text-sm); color: var(--color-text-secondary); line-height: 1.5; flex: 1; margin-bottom: var(--space-md); }
    .cp-college-footer { display: flex; gap: var(--space-sm); margin-top: auto; }

    /* Roadmap */
    .cp-roadmap { max-width: 780px; margin: 0 auto; position: relative; }
    .cp-roadmap-intro { text-align: center; margin-bottom: var(--space-2xl); color: var(--color-text-secondary); }
    .cp-roadmap-line {
      position: absolute; left: 42px; top: 60px; bottom: 60px; width: 3px;
      background: linear-gradient(180deg, var(--color-primary), #7c3aed, #059669, var(--color-warning));
      border-radius: 9999px;
    }
    .cp-roadmap-step {
      display: flex; gap: var(--space-lg); align-items: flex-start;
      margin-bottom: var(--space-xl); position: relative;
    }
    .cp-roadmap-step:last-child { margin-bottom: 0; }
    .cp-step-icon {
      width: 84px; height: 84px; flex-shrink: 0;
      border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;
      font-size: 1.5rem; border: 3px solid transparent;
      box-shadow: var(--shadow-md); position: relative; z-index: 1;
      background: var(--color-bg-card);
    }
    .cp-step-label { font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }
    .cp-step-body {
      flex: 1; background: var(--color-bg-card); border: 1px solid var(--color-border);
      border-radius: var(--radius-xl); padding: var(--space-lg);
      box-shadow: var(--shadow-sm); margin-top: var(--space-sm);
    }
    .cp-step-title { font-size: var(--text-lg); font-weight: 700; margin-bottom: var(--space-xs); }
    .cp-step-desc  { font-size: var(--text-sm); color: var(--color-text-secondary); line-height: 1.6; }

    /* Certs section inside roadmap */
    .cp-certs { margin-top: var(--space-xl); background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: var(--space-xl); }
    .cp-certs h3 { margin-bottom: var(--space-lg); font-size: var(--text-lg); }
    .cp-cert-list { list-style: none; display: flex; flex-direction: column; gap: var(--space-sm); }
    .cp-cert-item {
      display: flex; align-items: center; gap: var(--space-md);
      padding: var(--space-md); background: var(--color-surface);
      border-radius: var(--radius-lg); font-size: var(--text-sm); font-weight: 600;
    }
    .cp-cert-item::before { content: '🏅'; font-size: 1.2rem; }

    /* Skills chips */
    .cp-skills-grid { display: flex; flex-wrap: wrap; gap: var(--space-sm); margin-top: var(--space-md); }
    .cp-skill-chip {
      padding: 6px 14px; border-radius: var(--radius-full);
      background: var(--color-surface); color: var(--color-text-secondary);
      font-size: var(--text-sm); font-weight: 500; border: 1px solid var(--color-border);
    }

    /* Empty state */
    .cp-empty { text-align: center; padding: var(--space-3xl) var(--space-lg); color: var(--color-text-secondary); }
    .cp-empty-icon { font-size: 3rem; margin-bottom: var(--space-md); }

    /* No items hidden */
    .cp-card-hidden { display: none !important; }

    /* Toast */
    .cp-toast {
      position: fixed; bottom: 24px; right: 24px; z-index: 9999;
      padding: 12px 20px; border-radius: var(--radius-lg);
      background: #1e293b; color: #fff; font-size: var(--text-sm); font-weight: 600;
      box-shadow: var(--shadow-xl);
      opacity: 0; transform: translateY(12px);
      transition: all .3s ease; pointer-events: none;
    }
    .cp-toast.show { opacity: 1; transform: translateY(0); }

    /* Responsive */
    @media (max-width: 640px) {
      .cp-grid { grid-template-columns: 1fr; }
      .cp-hero  { padding: var(--space-lg); }
      .cp-roadmap-line { left: 32px; }
      .cp-step-icon { width: 64px; height: 64px; font-size: 1.1rem; }
    }
  </style>
</head>
<body>
  <!-- ── Header ── -->
  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="profile-setup.php">Profile</a>
          <a href="assessment.php">Assessment</a>
          <a href="test-results.php">Results</a>
          <a href="career-details.php">Careers</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <span style="color:var(--color-text-secondary);font-size:var(--text-sm);">Hi, <?php echo htmlspecialchars($user_name); ?></span>
        <a href="./logout.php" class="btn btn-primary">Logout</a>
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
    <a href="test-results.php">Results</a>
    <a href="career-details.php">Careers</a>
    <a href="./logout.php">Logout</a>
  </nav>

  <!-- ── Page Content ── -->
  <main class="cp-page">

    <!-- Breadcrumb -->
    <nav style="margin-bottom:var(--space-lg);font-size:var(--text-sm);color:var(--color-text-muted);">
      <a href="test-results.php" style="color:var(--color-primary);">← Back to Results</a>
      <span style="margin:0 8px;">›</span>
      <span><?php echo htmlspecialchars($career['title']); ?></span>
    </nav>

    <!-- Hero Banner -->
    <div class="cp-hero">
      <div class="cp-hero-inner">
        <div>
          <h1 class="cp-hero-title"><?php echo htmlspecialchars($career['title']); ?></h1>
          <p class="cp-hero-sub"><?php echo htmlspecialchars($career['overview'] ?? ''); ?></p>
          <div class="cp-hero-badges">
            <?php if ($match_score > 0): ?>
              <span class="cp-badge cp-badge-match">🎯 <?php echo $match_score; ?>% Match</span>
            <?php endif; ?>
            <?php if (!empty($career['salary'])): ?>
              <span class="cp-badge cp-badge-salary">💰 <?php echo htmlspecialchars($career['salary']); ?></span>
            <?php endif; ?>
            <?php if (!empty($career['category'])): ?>
              <span class="cp-badge cp-badge-cat">📂 <?php echo htmlspecialchars($career['category']); ?></span>
            <?php endif; ?>
            <span class="cp-badge cp-badge-cat">📚 <?php echo count($courses); ?> Courses</span>
            <span class="cp-badge cp-badge-cat">🏛️ <?php echo count($colleges); ?> Institutes</span>
          </div>
        </div>
        <?php if ($match_score > 0): ?>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
          <div class="cp-match-ring" title="<?php echo $match_score; ?>% career match">
            <div class="cp-match-ring-inner">
              <div class="cp-match-ring-pct"><?php echo $match_score; ?>%</div>
              <div class="cp-match-ring-lbl">Match</div>
            </div>
          </div>
          <button class="btn <?php echo $is_career_saved ? 'btn-secondary' : 'btn-outline'; ?>" 
                  onclick="toggleSave(this, 'career', <?php echo $career_id; ?>)"
                  style="width: 100%; justify-content: center; display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
            <?php echo $is_career_saved ? '❤️ Saved' : '🤍 Save Career'; ?>
          </button>
        </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($career_skills)): ?>
      <div style="margin-top:var(--space-lg);">
        <div style="font-size:var(--text-sm);font-weight:600;color:var(--color-text-secondary);margin-bottom:var(--space-sm);">Key Skills for This Career:</div>
        <div class="cp-skills-grid">
          <?php foreach ($career_skills as $skill): ?>
            <span class="cp-skill-chip"><?php echo htmlspecialchars($skill); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tab Navigator -->
    <div class="cp-tabs" role="tablist">
      <button class="cp-tab active" id="tab-courses"  onclick="switchPanel('courses')"  role="tab" aria-selected="true">
        <span class="cp-tab-emoji">📚</span>Online Courses
        <span style="margin-left:6px;font-size:.75rem;background:var(--color-primary-light);color:var(--color-primary);padding:2px 7px;border-radius:99px;"><?php echo count($courses); ?></span>
      </button>
      <button class="cp-tab" id="tab-colleges" onclick="switchPanel('colleges')" role="tab" aria-selected="false">
        <span class="cp-tab-emoji">🏛️</span>Colleges &amp; Institutes
        <span style="margin-left:6px;font-size:.75rem;background:var(--color-primary-light);color:var(--color-primary);padding:2px 7px;border-radius:99px;"><?php echo count($colleges); ?></span>
      </button>
      <button class="cp-tab" id="tab-roadmap"  onclick="switchPanel('roadmap')"  role="tab" aria-selected="false">
        <span class="cp-tab-emoji">🗺️</span>Learning Roadmap
      </button>
    </div>

    <!-- ══ Panel: Courses ══ -->
    <div class="cp-panel active" id="panel-courses">

      <!-- Filter bar -->
      <div class="cp-filter-bar">
        <span class="cp-filter-label">Filter by:</span>
        <button class="cp-filter-btn active" id="cf-all"   onclick="filterCourses('all')">All</button>
        <button class="cp-filter-btn"        id="cf-free"  onclick="filterCourses('free')">🆓 Free</button>
        <button class="cp-filter-btn"        id="cf-paid"  onclick="filterCourses('paid')">💳 Paid</button>
        <span style="width:1px;height:24px;background:var(--color-border);margin:0 4px;"></span>
        <button class="cp-filter-btn" id="cl-beg"   onclick="filterLevel('beginner')">Beginner</button>
        <button class="cp-filter-btn" id="cl-inter"  onclick="filterLevel('intermediate')">Intermediate</button>
        <button class="cp-filter-btn" id="cl-adv"   onclick="filterLevel('advanced')">Advanced</button>
      </div>

      <?php if (empty($courses)): ?>
        <div class="cp-empty"><div class="cp-empty-icon">📭</div><p>No courses available yet for this career. Check back soon!</p></div>
      <?php else: ?>
      <div class="cp-grid" id="coursesGrid">
        <?php foreach ($courses as $course):
          $is_saved  = in_array($course['id'], $saved_courses);
          $level_cls = match($course['course_level']) { 'intermediate' => 'inter', 'advanced' => 'adv', default => 'beg' };
          $level_lbl = ucfirst($course['course_level']);
          $stars     = $course['rating'] ? round($course['rating']) : 0;
        ?>
        <div class="cp-course-card"
             data-free="<?php echo $course['is_free'] ? 'free' : 'paid'; ?>"
             data-level="<?php echo htmlspecialchars($course['course_level']); ?>">
          <div class="cp-course-header">
            <span class="cp-course-platform"><?php echo htmlspecialchars($course['platform']); ?></span>
          </div>
          <div class="cp-course-title"><?php echo htmlspecialchars($course['title']); ?></div>
          <div class="cp-course-desc"><?php echo htmlspecialchars($course['description'] ?? ''); ?></div>
          <div class="cp-course-meta">
            <span class="cp-meta-pill <?php echo $course['is_free'] ? 'free' : 'paid'; ?>">
              <?php echo $course['is_free'] ? '🆓 Free' : '💳 Paid'; ?>
            </span>
            <span class="cp-meta-pill <?php echo $level_cls; ?>"><?php echo $level_lbl; ?></span>
            <?php if (!empty($course['duration'])): ?>
              <span class="cp-meta-pill">⏱ <?php echo htmlspecialchars($course['duration']); ?></span>
            <?php endif; ?>
            <?php if (!empty($course['rating'])): ?>
              <span class="cp-rating">
                <?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?>
                <?php echo number_format($course['rating'], 1); ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="cp-course-footer">
            <a href="<?php echo htmlspecialchars($course['url']); ?>" target="_blank" rel="noopener" class="cp-link-btn">
              Enroll Now →
            </a>
            <button class="cp-save-btn <?php echo $is_saved ? 'saved' : ''; ?>"
                    onclick="toggleSave(this, 'course', <?php echo $course['id']; ?>)"
                    title="<?php echo $is_saved ? 'Saved' : 'Save for later'; ?>">
              <?php echo $is_saved ? '❤️' : '🤍'; ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="cp-empty" id="courses-empty-msg" style="display:none;">
        <div class="cp-empty-icon">🔍</div><p>No courses match this filter. Try a different selection.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ Panel: Colleges ══ -->
    <div class="cp-panel" id="panel-colleges">

      <div class="cp-filter-bar">
        <span class="cp-filter-label">Filter by mode:</span>
        <button class="cp-filter-btn active" id="col-all"     onclick="filterColleges('all')">All</button>
        <button class="cp-filter-btn"        id="col-online"  onclick="filterColleges('online')">🌐 Online</button>
        <button class="cp-filter-btn"        id="col-offline" onclick="filterColleges('offline')">🏫 Offline</button>
        <button class="cp-filter-btn"        id="col-hybrid"  onclick="filterColleges('hybrid')">⚡ Hybrid</button>
      </div>

      <?php if (empty($colleges)): ?>
        <div class="cp-empty"><div class="cp-empty-icon">🏫</div><p>No institutes listed yet. Check back soon!</p></div>
      <?php else: ?>
      <div class="cp-grid" id="collegesGrid">
        <?php foreach ($colleges as $college):
          $is_saved = in_array($college['id'], $saved_colleges);
          $type_cls = 'cp-type-' . ($college['type'] ?? 'institute');
          $mode_cls = 'cp-mode-' . ($college['mode'] ?? 'offline');
          $type_lbl = ucwords(str_replace('_', ' ', $college['type'] ?? 'institute'));
          $mode_lbl = ucfirst($college['mode'] ?? 'offline');
          $mode_icon= match($college['mode']) { 'online' => '🌐', 'hybrid' => '⚡', default => '🏫' };
        ?>
        <div class="cp-college-card" data-mode="<?php echo htmlspecialchars($college['mode']); ?>">
          <div class="cp-college-header">
            <div class="cp-college-badges">
              <span class="cp-type-badge <?php echo $type_cls; ?>"><?php echo $type_lbl; ?></span>
              <span class="cp-mode-badge <?php echo $mode_cls; ?>"><?php echo $mode_icon . ' ' . $mode_lbl; ?></span>
            </div>
            <div class="cp-college-name"><?php echo htmlspecialchars($college['name']); ?></div>
            <?php if (!empty($college['location'])): ?>
              <div class="cp-college-location">📍 <?php echo htmlspecialchars($college['location']); ?></div>
            <?php endif; ?>
            <?php if (!empty($college['course_offered'])): ?>
              <div class="cp-college-course">🎓 <?php echo htmlspecialchars($college['course_offered']); ?></div>
            <?php endif; ?>
          </div>
          <div class="cp-college-desc"><?php echo htmlspecialchars($college['description'] ?? ''); ?></div>
          <div class="cp-college-footer">
            <?php if (!empty($college['website_url'])): ?>
              <a href="<?php echo htmlspecialchars($college['website_url']); ?>" target="_blank" rel="noopener" class="cp-link-btn" style="flex:1;">Visit Website →</a>
            <?php endif; ?>
            <button class="cp-save-btn <?php echo $is_saved ? 'saved' : ''; ?>"
                    onclick="toggleSave(this, 'college', <?php echo $college['id']; ?>)"
                    title="<?php echo $is_saved ? 'Saved' : 'Save for later'; ?>">
              <?php echo $is_saved ? '❤️' : '🤍'; ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="cp-empty" id="colleges-empty-msg" style="display:none;">
        <div class="cp-empty-icon">🔍</div><p>No institutes match this filter.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ Panel: Roadmap ══ -->
    <div class="cp-panel" id="panel-roadmap">
      <?php if (empty($roadmap_stages)): ?>
        <div class="cp-empty"><div class="cp-empty-icon">🗺️</div><p>Roadmap not available for this career yet.</p></div>
      <?php else: ?>
      <div class="cp-roadmap">
        <p class="cp-roadmap-intro">Follow these steps to go from beginner to job-ready in <strong><?php echo htmlspecialchars($career['title']); ?></strong>.</p>
        <div class="cp-roadmap-line"></div>
        <?php foreach ($roadmap_stages as $stage): ?>
        <div class="cp-roadmap-step">
          <div class="cp-step-icon" style="border-color:<?php echo $stage['color']; ?>;color:<?php echo $stage['color']; ?>;">
            <?php echo $stage['icon']; ?>
            <span class="cp-step-label" style="color:<?php echo $stage['color']; ?>;"><?php echo $stage['label']; ?></span>
          </div>
          <div class="cp-step-body">
            <div class="cp-step-title"><?php echo htmlspecialchars($stage['title']); ?></div>
            <?php if (!empty($stage['desc'])): ?>
              <div class="cp-step-desc"><?php echo htmlspecialchars($stage['desc']); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Certifications -->
        <?php if (!empty($career_certs)): ?>
        <div class="cp-certs">
          <h3>🏅 Recommended Certifications</h3>
          <ul class="cp-cert-list">
            <?php foreach ($career_certs as $cert): ?>
              <li class="cp-cert-item"><?php echo htmlspecialchars($cert); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <!-- Job Roles -->
        <?php if (!empty($career_roles)): ?>
        <div class="cp-certs" style="margin-top:var(--space-lg);">
          <h3>💼 Possible Job Roles</h3>
          <div class="cp-skills-grid">
            <?php foreach ($career_roles as $role): ?>
              <span class="cp-skill-chip"><?php echo htmlspecialchars($role); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

  </main>

  <!-- Toast notification -->
  <div class="cp-toast" id="cpToast"></div>

  <script>
  // ── Theme toggle ──
  (function() {
    var theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    var btn = document.getElementById('themeToggle');
    var sun = btn && btn.querySelector('.icon-sun');
    var moon = btn && btn.querySelector('.icon-moon');
    if (btn) {
      if (theme === 'dark') { if(sun) sun.classList.add('sr-only'); if(moon) moon.classList.remove('sr-only'); }
      else { if(sun) sun.classList.remove('sr-only'); if(moon) moon.classList.add('sr-only'); }
      btn.addEventListener('click', function() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var nt = isDark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nt);
        localStorage.setItem('theme', nt);
        if(sun) sun.classList.toggle('sr-only', !isDark);
        if(moon) moon.classList.toggle('sr-only', isDark);
      });
    }
    // hamburger
    var hb = document.getElementById('hamburger'), mn = document.getElementById('mobileNav');
    if (hb && mn) {
      hb.addEventListener('click', function() { hb.classList.toggle('open'); mn.classList.toggle('open'); });
      mn.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', function() { hb.classList.remove('open'); mn.classList.remove('open'); }); });
    }
  })();

  // ── Tab switching ──
  function switchPanel(name) {
    document.querySelectorAll('.cp-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.cp-tab').forEach(function(t) { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
    document.getElementById('panel-' + name).classList.add('active');
    var tabEl = document.getElementById('tab-' + name);
    if (tabEl) { tabEl.classList.add('active'); tabEl.setAttribute('aria-selected','true'); }
  }

  // ── Course filters ──
  var activeCourseFilter = 'all', activeLevelFilter = 'all';
  function filterCourses(type) {
    activeCourseFilter = type;
    ['all','free','paid'].forEach(function(k) {
      document.getElementById('cf-' + k)?.classList.toggle('active', k === type);
    });
    applyCourseFilters();
  }
  function filterLevel(lvl) {
    activeLevelFilter = (activeLevelFilter === lvl) ? 'all' : lvl;
    ['beg','inter','adv'].forEach(function(k) {
      var map = {beg:'beginner',inter:'intermediate',adv:'advanced'};
      document.getElementById('cl-' + k)?.classList.toggle('active', map[k] === activeLevelFilter);
    });
    applyCourseFilters();
  }
  function applyCourseFilters() {
    var cards = document.querySelectorAll('#coursesGrid .cp-course-card');
    var visible = 0;
    cards.forEach(function(c) {
      var matchType  = activeCourseFilter === 'all' || c.dataset.free === activeCourseFilter;
      var matchLevel = activeLevelFilter  === 'all' || c.dataset.level === activeLevelFilter;
      if (matchType && matchLevel) { c.classList.remove('cp-card-hidden'); visible++; }
      else c.classList.add('cp-card-hidden');
    });
    var msg = document.getElementById('courses-empty-msg');
    if (msg) msg.style.display = (visible === 0) ? 'block' : 'none';
  }

  // ── College filters ──
  function filterColleges(mode) {
    ['all','online','offline','hybrid'].forEach(function(k) {
      document.getElementById('col-' + k)?.classList.toggle('active', k === mode);
    });
    document.querySelectorAll('#collegesGrid .cp-college-card').forEach(function(c) {
      var show = (mode === 'all') || (c.dataset.mode === mode);
      c.classList.toggle('cp-card-hidden', !show);
    });
    var visible = document.querySelectorAll('#collegesGrid .cp-college-card:not(.cp-card-hidden)').length;
    var msg = document.getElementById('colleges-empty-msg');
    if (msg) msg.style.display = (visible === 0) ? 'block' : 'none';
  }

  // ── Save for later ──
  function toggleSave(btn, type, id) {
    btn.classList.add('saving');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/save-resource.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      btn.classList.remove('saving');
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          var saved = res.saved;
          btn.classList.toggle('saved', saved);
          btn.innerHTML = saved ? '❤️' : '🤍';
          btn.title = saved ? 'Saved' : 'Save for later';
          showToast(saved ? '❤️ Saved for later!' : '🗑️ Removed from saved');
        } else {
          showToast('⚠️ ' + (res.message || 'Something went wrong'));
        }
      } catch(e) { showToast('⚠️ Could not save. Please try again.'); }
    };
    xhr.onerror = function() { btn.classList.remove('saving'); showToast('⚠️ Network error.'); };
    xhr.send('type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '&csrf=<?php echo $csrf_token; ?>');
  }

  // ── Toast helper ──
  function showToast(msg) {
    var t = document.getElementById('cpToast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2800);
  }
  </script>
</body>
</html>
