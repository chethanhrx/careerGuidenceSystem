<?php
require_once '../config.php';

// Admin guard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];
$message = '';
$message_type = '';

// ── Handle POST (add / update) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $career_id   = intval($_POST['career_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $platform    = trim($_POST['platform'] ?? '');
    $url         = trim($_POST['url'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $rating      = !empty($_POST['rating']) ? floatval($_POST['rating']) : null;
    $is_free     = isset($_POST['is_free']) ? 1 : 0;
    $level       = in_array($_POST['level'] ?? '', ['beginner','intermediate','advanced']) ? $_POST['level'] : 'beginner';
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($platform) || empty($url) || $career_id <= 0) {
        $message = 'Title, platform, URL, and career are required.';
        $message_type = 'error';
    } else {
        // Sanitise
        $title_s = mysqli_real_escape_string($conn, $title);
        $platform_s = mysqli_real_escape_string($conn, $platform);
        $url_s = mysqli_real_escape_string($conn, $url);
        $duration_s = mysqli_real_escape_string($conn, $duration);
        $desc_s = mysqli_real_escape_string($conn, $description);
        $rating_s = ($rating !== null) ? $rating : 'NULL';

        if ($id > 0) {
            $sql = "UPDATE courses SET career_id=$career_id, title='$title_s', platform='$platform_s',
                    url='$url_s', duration='$duration_s', rating=$rating_s, is_free=$is_free,
                    course_level='$level', description='$desc_s' WHERE id=$id";
            if (mysqli_query($conn, $sql)) { header("Location: courses.php?success=updated"); exit(); }
            else { $message = 'Update failed: ' . mysqli_error($conn); $message_type = 'error'; }
        } else {
            $sql = "INSERT INTO courses (career_id, title, platform, url, duration, rating, is_free, course_level, description)
                    VALUES ($career_id,'$title_s','$platform_s','$url_s','$duration_s'," . ($rating !== null ? $rating : 'NULL') . ",$is_free,'$level','$desc_s')";
            if (mysqli_query($conn, $sql)) { header("Location: courses.php?success=added"); exit(); }
            else { $message = 'Insert failed: ' . mysqli_error($conn); $message_type = 'error'; }
        }
    }
}

// ── Handle DELETE ──
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM courses WHERE id=$del_id")) {
        header("Location: courses.php?success=deleted"); exit();
    } else {
        $message = 'Delete failed: ' . mysqli_error($conn);
        $message_type = 'error';
    }
}

// ── Success messages ──
if (isset($_GET['success'])) {
    $msgs = ['added' => 'Course added successfully!', 'updated' => 'Course updated!', 'deleted' => 'Course deleted.'];
    $message = $msgs[$_GET['success']] ?? '';
    $message_type = 'success';
}

// ── Fetch all careers (for dropdown) ──
$careers_result = mysqli_query($conn, "SELECT id, title FROM careers ORDER BY title ASC");
$all_careers = [];
while ($r = mysqli_fetch_assoc($careers_result)) $all_careers[] = $r;

// ── Fetch all courses ──
$all_courses = [];
try {
    $courses_result = @mysqli_query($conn, "SELECT c.*, ca.title AS career_title FROM courses c LEFT JOIN careers ca ON c.career_id = ca.id ORDER BY ca.title, c.course_level, c.title");
    if ($courses_result) {
        while ($r = mysqli_fetch_assoc($courses_result)) $all_courses[] = $r;
    }
} catch (Exception $e) {}

// ── Fetch course for editing ──
$edit_course = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = mysqli_query($conn, "SELECT * FROM courses WHERE id=$edit_id");
    if ($res && mysqli_num_rows($res) > 0) $edit_course = mysqli_fetch_assoc($res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Courses | CareerGuide Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .admin-header { padding: var(--space-lg); border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-md); }
    .admin-main   { padding: var(--space-lg); max-width: 1400px; margin: 0 auto; }
    .item-list    { display: grid; gap: var(--space-md); margin-top: var(--space-lg); }
    .item-card    { display: flex; align-items: flex-start; justify-content: space-between; padding: var(--space-lg); border: 1px solid var(--color-border); border-radius: var(--radius-lg); flex-wrap: wrap; gap: var(--space-md); background: var(--color-bg-card); transition: box-shadow .2s; }
    .item-card:hover { box-shadow: var(--shadow-md); }
    .item-content { flex: 1; min-width: 280px; }
    .item-actions { display: flex; gap: var(--space-sm); flex-wrap: wrap; align-items: center; }
    .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: var(--space-lg); opacity: 0; visibility: hidden; transition: opacity .3s, visibility .3s; }
    .modal.open { opacity: 1; visibility: visible; }
    .modal-content { background: var(--color-bg-card); border-radius: var(--radius-xl); padding: var(--space-xl); width: 100%; max-width: 720px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-xl); }
    .modal-actions { margin-top: var(--space-lg); display: flex; gap: var(--space-md); justify-content: flex-end; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); }
    @media(max-width:640px) { .form-row { grid-template-columns: 1fr; } }
    .msg { padding: var(--space-md); border-radius: var(--radius-md); margin-bottom: var(--space-lg); }
    .msg.success { background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; }
    .msg.error   { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; }
    .pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 600; }
    .pill-free { background:#d1fae5; color:#059669; }
    .pill-paid { background:#fef3c7; color:#d97706; }
    .pill-beg   { background:#dbeafe; color:#1d4ed8; }
    .pill-inter { background:#ede9fe; color:#7c3aed; }
    .pill-adv   { background:#fce7f3; color:#be185d; }
    .pill-career { background: var(--color-surface); color: var(--color-text-secondary); }
    .stats-row { display: flex; gap: var(--space-md); flex-wrap: wrap; margin-bottom: var(--space-xl); }
    .stat-mini { flex: 1; min-width: 120px; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-md); text-align: center; }
    .stat-mini strong { display: block; font-size: 1.5rem; font-weight: 800; color: var(--color-primary); }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span> Admin</a>
        <nav class="nav-links">
          <a href="index.php">Dashboard</a>
          <a href="careers.php">Careers</a>
          <a href="courses.php" class="active">Courses</a>
          <a href="colleges.php">Institutes</a>
          <a href="questions.php">Questions</a>
          <a href="users.php">Users</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <span style="color:var(--color-text-secondary);font-size:var(--text-sm);">Hi, <?php echo htmlspecialchars($admin_name); ?></span>
        <a href="../index.php" target="_blank" class="btn btn-ghost">View Site</a>
        <a href="../logout.php" class="btn btn-primary">Logout</a>
        <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>

  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php">Dashboard</a>
    <a href="careers.php">Careers</a>
    <a href="courses.php" class="active">Courses</a>
    <a href="colleges.php">Institutes</a>
    <a href="questions.php">Questions</a>
    <a href="users.php">Users</a>
    <a href="../logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1 style="font-size:var(--text-xl);margin:0;">📚 Manage Courses</h1>
    <button class="btn btn-primary" onclick="openModal()">+ Add New Course</button>
  </div>

  <main class="admin-main">
    <?php if ($message): ?>
      <div class="msg <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-mini"><strong><?php echo count($all_courses); ?></strong>Total Courses</div>
      <div class="stat-mini"><strong><?php echo count(array_filter($all_courses, fn($c) => $c['is_free'])); ?></strong>Free</div>
      <div class="stat-mini"><strong><?php echo count(array_filter($all_courses, fn($c) => !$c['is_free'])); ?></strong>Paid</div>
      <div class="stat-mini"><strong><?php echo count($all_careers); ?></strong>Careers</div>
    </div>

    <?php if (empty($all_courses)): ?>
      <div style="text-align:center;padding:var(--space-3xl);color:var(--color-text-secondary);">
        <div style="font-size:3rem;margin-bottom:var(--space-md);">📭</div>
        <p>No courses yet. Add your first course!</p>
        <button class="btn btn-primary" onclick="openModal()" style="margin-top:var(--space-lg);">Add First Course</button>
      </div>
    <?php else: ?>
      <div class="item-list">
        <?php foreach ($all_courses as $course):
          $level_cls = ['beginner'=>'beg','intermediate'=>'inter','advanced'=>'adv'][$course['course_level']] ?? 'beg';
        ?>
          <div class="item-card">
            <div class="item-content">
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:var(--space-sm);">
                <span class="pill pill-career"><?php echo htmlspecialchars($course['career_title'] ?? '—'); ?></span>
                <span class="pill pill-<?php echo $course['is_free'] ? 'free' : 'paid'; ?>"><?php echo $course['is_free'] ? '🆓 Free' : '💳 Paid'; ?></span>
                <span class="pill pill-<?php echo $level_cls; ?>"><?php echo ucfirst($course['course_level']); ?></span>
                <?php if ($course['rating']): ?>
                  <span class="pill" style="background:var(--color-warning-bg,#fef3c7);color:var(--color-warning);">★ <?php echo number_format($course['rating'],1); ?></span>
                <?php endif; ?>
              </div>
              <strong><?php echo htmlspecialchars($course['title']); ?></strong>
              <div style="font-size:var(--text-sm);color:var(--color-text-secondary);margin-top:4px;">
                <?php echo htmlspecialchars($course['platform']); ?>
                <?php if (!empty($course['duration'])): ?> &mdash; <?php echo htmlspecialchars($course['duration']); ?><?php endif; ?>
              </div>
              <?php if (!empty($course['description'])): ?>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted);margin-top:6px;">
                  <?php echo htmlspecialchars(mb_substr($course['description'], 0, 120)) . (mb_strlen($course['description']) > 120 ? '…' : ''); ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="item-actions">
              <a href="courses.php?edit=<?php echo $course['id']; ?>" class="btn btn-secondary">Edit</a>
              <a href="<?php echo htmlspecialchars($course['url']); ?>" target="_blank" class="btn btn-ghost">↗ View</a>
              <a href="courses.php?delete=<?php echo $course['id']; ?>"
                 class="btn btn-ghost" style="color:var(--color-error);"
                 onclick="return confirm('Delete this course?')">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- ── Modal ── -->
  <div class="modal <?php echo $edit_course ? 'open' : ''; ?>" id="courseModal">
    <div class="modal-content">
      <h2><?php echo $edit_course ? '✏️ Edit Course' : '➕ Add New Course'; ?></h2>
      <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $edit_course['id'] ?? ''; ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Career *
              <select name="career_id" class="form-select" required>
                <option value="">— Select Career —</option>
                <?php foreach ($all_careers as $c): ?>
                  <option value="<?php echo $c['id']; ?>" <?php echo (($edit_course['career_id'] ?? 0) == $c['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Level
              <select name="level" class="form-select">
                <?php foreach (['beginner','intermediate','advanced'] as $lvl): ?>
                  <option value="<?php echo $lvl; ?>" <?php echo (($edit_course['course_level'] ?? 'beginner') === $lvl) ? 'selected' : ''; ?>><?php echo ucfirst($lvl); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Course Title *
            <input type="text" name="title" class="form-input" placeholder="e.g. Google Data Analytics Professional Certificate" required value="<?php echo htmlspecialchars($edit_course['title'] ?? ''); ?>">
          </label>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Platform *
              <input type="text" name="platform" class="form-input" placeholder="e.g. Coursera, Udemy, edX" required value="<?php echo htmlspecialchars($edit_course['platform'] ?? ''); ?>">
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Duration
              <input type="text" name="duration" class="form-input" placeholder="e.g. 6 months, 40 hours" value="<?php echo htmlspecialchars($edit_course['duration'] ?? ''); ?>">
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Course URL *
            <input type="url" name="url" class="form-input" placeholder="https://..." required value="<?php echo htmlspecialchars($edit_course['url'] ?? ''); ?>">
          </label>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Rating (optional)
              <input type="number" name="rating" class="form-input" placeholder="e.g. 4.7" min="1" max="5" step="0.1" value="<?php echo $edit_course['rating'] ?? ''; ?>">
            </label>
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:var(--space-md);margin-top:var(--space-lg);">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
              <input type="checkbox" name="is_free" value="1" <?php echo ($edit_course['is_free'] ?? 0) ? 'checked' : ''; ?> style="width:18px;height:18px;">
              This course is Free 🆓
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description
            <textarea name="description" class="form-textarea" rows="3" placeholder="Brief description of what students will learn..."><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
          </label>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary"><?php echo $edit_course ? 'Save Changes' : 'Add Course'; ?></button>
        </div>
      </form>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
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
        document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
        localStorage.setItem('theme', isDark ? 'light' : 'dark');
        if(sun) sun.classList.toggle('sr-only', !isDark);
        if(moon) moon.classList.toggle('sr-only', isDark);
      });
    }
    var hb = document.getElementById('hamburger'), mn = document.getElementById('mobileNav');
    if (hb && mn) {
      hb.addEventListener('click', function() { hb.classList.toggle('open'); mn.classList.toggle('open'); });
      mn.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', function() { hb.classList.remove('open'); mn.classList.remove('open'); }); });
    }
    document.getElementById('courseModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
    <?php if ($edit_course): ?>
    document.getElementById('courseModal').classList.add('open');
    <?php endif; ?>
  });
  function openModal()  { document.getElementById('courseModal').classList.add('open'); }
  function closeModal() { document.getElementById('courseModal').classList.remove('open'); }
  </script>
</body>
</html>
