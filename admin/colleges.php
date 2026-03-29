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
    $id              = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $career_id       = intval($_POST['career_id'] ?? 0);
    $name            = trim($_POST['name'] ?? '');
    $location        = trim($_POST['location'] ?? '');
    $course_offered  = trim($_POST['course_offered'] ?? '');
    $website_url     = trim($_POST['website_url'] ?? '');
    $type            = in_array($_POST['type'] ?? '', ['college','institute','training_center','bootcamp']) ? $_POST['type'] : 'institute';
    $mode            = in_array($_POST['mode'] ?? '', ['online','offline','hybrid']) ? $_POST['mode'] : 'offline';
    $description     = trim($_POST['description'] ?? '');

    if (empty($name) || $career_id <= 0) {
        $message = 'Name and career are required.';
        $message_type = 'error';
    } else {
        $name_s    = mysqli_real_escape_string($conn, $name);
        $loc_s     = mysqli_real_escape_string($conn, $location);
        $course_s  = mysqli_real_escape_string($conn, $course_offered);
        $url_s     = mysqli_real_escape_string($conn, $website_url);
        $desc_s    = mysqli_real_escape_string($conn, $description);

        if ($id > 0) {
            $sql = "UPDATE colleges SET career_id=$career_id, name='$name_s', location='$loc_s',
                    course_offered='$course_s', website_url='$url_s', type='$type',
                    mode='$mode', description='$desc_s' WHERE id=$id";
            if (mysqli_query($conn, $sql)) { header("Location: colleges.php?success=updated"); exit(); }
            else { $message = 'Update failed: ' . mysqli_error($conn); $message_type = 'error'; }
        } else {
            $sql = "INSERT INTO colleges (career_id, name, location, course_offered, website_url, type, mode, description)
                    VALUES ($career_id,'$name_s','$loc_s','$course_s','$url_s','$type','$mode','$desc_s')";
            if (mysqli_query($conn, $sql)) { header("Location: colleges.php?success=added"); exit(); }
            else { $message = 'Insert failed: ' . mysqli_error($conn); $message_type = 'error'; }
        }
    }
}

// ── Handle DELETE ──
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM colleges WHERE id=$del_id")) {
        header("Location: colleges.php?success=deleted"); exit();
    } else {
        $message = 'Delete failed: ' . mysqli_error($conn); $message_type = 'error';
    }
}

// ── Success messages ──
if (isset($_GET['success'])) {
    $msgs = ['added' => 'Institute added successfully!', 'updated' => 'Institute updated!', 'deleted' => 'Institute deleted.'];
    $message = $msgs[$_GET['success']] ?? ''; $message_type = 'success';
}

// ── Fetch all careers ──
$careers_result = mysqli_query($conn, "SELECT id, title FROM careers ORDER BY title ASC");
$all_careers = [];
while ($r = mysqli_fetch_assoc($careers_result)) $all_careers[] = $r;

// ── Fetch all colleges ──
$all_colleges = [];
try {
    $colleges_result = @mysqli_query($conn, "SELECT co.*, ca.title AS career_title FROM colleges co LEFT JOIN careers ca ON co.career_id = ca.id ORDER BY ca.title, co.name");
    if ($colleges_result) {
        while ($r = mysqli_fetch_assoc($colleges_result)) $all_colleges[] = $r;
    }
} catch (Exception $e) {}

// ── Fetch college for editing ──
$edit_college = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = mysqli_query($conn, "SELECT * FROM colleges WHERE id=$edit_id");
    if ($res && mysqli_num_rows($res) > 0) $edit_college = mysqli_fetch_assoc($res);
}

$type_labels = ['college' => '🎓 College', 'institute' => '🏛 Institute', 'training_center' => '🔬 Training Center', 'bootcamp' => '⚡ Bootcamp'];
$mode_labels = ['online' => '🌐 Online', 'offline' => '🏫 Offline', 'hybrid' => '⚡ Hybrid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Institutes | CareerGuide Admin</title>
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
    .pill-college        { background:#dbeafe; color:#1d4ed8; }
    .pill-institute      { background:#ede9fe; color:#7c3aed; }
    .pill-training_center{ background:#fce7f3; color:#be185d; }
    .pill-bootcamp       { background:#d1fae5; color:#059669; }
    .pill-online  { background:#d1fae5; color:#059669; }
    .pill-offline { background:#fef3c7; color:#d97706; }
    .pill-hybrid  { background:#e0f2fe; color:#0284c7; }
    .pill-career  { background: var(--color-surface); color: var(--color-text-secondary); }
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
          <a href="courses.php">Courses</a>
          <a href="colleges.php" class="active">Institutes</a>
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
    <a href="courses.php">Courses</a>
    <a href="colleges.php" class="active">Institutes</a>
    <a href="questions.php">Questions</a>
    <a href="users.php">Users</a>
    <a href="../logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1 style="font-size:var(--text-xl);margin:0;">🏛️ Manage Colleges &amp; Institutes</h1>
    <button class="btn btn-primary" onclick="openModal()">+ Add New Institute</button>
  </div>

  <main class="admin-main">
    <?php if ($message): ?>
      <div class="msg <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-mini"><strong><?php echo count($all_colleges); ?></strong>Total</div>
      <div class="stat-mini"><strong><?php echo count(array_filter($all_colleges, fn($c) => $c['mode']==='online')); ?></strong>Online</div>
      <div class="stat-mini"><strong><?php echo count(array_filter($all_colleges, fn($c) => $c['mode']==='offline')); ?></strong>Offline</div>
      <div class="stat-mini"><strong><?php echo count(array_filter($all_colleges, fn($c) => $c['mode']==='hybrid')); ?></strong>Hybrid</div>
    </div>

    <?php if (empty($all_colleges)): ?>
      <div style="text-align:center;padding:var(--space-3xl);color:var(--color-text-secondary);">
        <div style="font-size:3rem;margin-bottom:var(--space-md);">🏫</div>
        <p>No institutes yet. Add your first one!</p>
        <button class="btn btn-primary" onclick="openModal()" style="margin-top:var(--space-lg);">Add First Institute</button>
      </div>
    <?php else: ?>
      <div class="item-list">
        <?php foreach ($all_colleges as $col): ?>
          <div class="item-card">
            <div class="item-content">
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:var(--space-sm);">
                <span class="pill pill-career"><?php echo htmlspecialchars($col['career_title'] ?? '—'); ?></span>
                <span class="pill pill-<?php echo $col['type']; ?>"><?php echo $type_labels[$col['type']] ?? $col['type']; ?></span>
                <span class="pill pill-<?php echo $col['mode']; ?>"><?php echo $mode_labels[$col['mode']] ?? $col['mode']; ?></span>
              </div>
              <strong><?php echo htmlspecialchars($col['name']); ?></strong>
              <?php if (!empty($col['location'])): ?>
                <div style="font-size:var(--text-sm);color:var(--color-text-secondary);margin-top:4px;">📍 <?php echo htmlspecialchars($col['location']); ?></div>
              <?php endif; ?>
              <?php if (!empty($col['course_offered'])): ?>
                <div style="font-size:var(--text-sm);color:var(--color-text-secondary);margin-top:2px;">🎓 <?php echo htmlspecialchars($col['course_offered']); ?></div>
              <?php endif; ?>
              <?php if (!empty($col['description'])): ?>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted);margin-top:6px;">
                  <?php echo htmlspecialchars(mb_substr($col['description'], 0, 120)) . (mb_strlen($col['description']) > 120 ? '…' : ''); ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="item-actions">
              <a href="colleges.php?edit=<?php echo $col['id']; ?>" class="btn btn-secondary">Edit</a>
              <?php if (!empty($col['website_url'])): ?>
                <a href="<?php echo htmlspecialchars($col['website_url']); ?>" target="_blank" class="btn btn-ghost">↗ Visit</a>
              <?php endif; ?>
              <a href="colleges.php?delete=<?php echo $col['id']; ?>"
                 class="btn btn-ghost" style="color:var(--color-error);"
                 onclick="return confirm('Delete this institute?')">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- ── Modal ── -->
  <div class="modal <?php echo $edit_college ? 'open' : ''; ?>" id="collegeModal">
    <div class="modal-content">
      <h2><?php echo $edit_college ? '✏️ Edit Institute' : '➕ Add New Institute'; ?></h2>
      <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $edit_college['id'] ?? ''; ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Career *
              <select name="career_id" class="form-select" required>
                <option value="">— Select Career —</option>
                <?php foreach ($all_careers as $c): ?>
                  <option value="<?php echo $c['id']; ?>" <?php echo (($edit_college['career_id'] ?? 0) == $c['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Type
              <select name="type" class="form-select">
                <?php foreach (['college'=>'College','institute'=>'Institute','training_center'=>'Training Center','bootcamp'=>'Bootcamp'] as $v => $l): ?>
                  <option value="<?php echo $v; ?>" <?php echo (($edit_college['type'] ?? 'institute') === $v) ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Institute Name *
            <input type="text" name="name" class="form-input" placeholder="e.g. IIT Bombay Online Degree" required value="<?php echo htmlspecialchars($edit_college['name'] ?? ''); ?>">
          </label>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Location
              <input type="text" name="location" class="form-input" placeholder="e.g. Mumbai, MH (Online)" value="<?php echo htmlspecialchars($edit_college['location'] ?? ''); ?>">
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Mode
              <select name="mode" class="form-select">
                <?php foreach (['online'=>'Online','offline'=>'Offline','hybrid'=>'Hybrid'] as $v => $l): ?>
                  <option value="<?php echo $v; ?>" <?php echo (($edit_college['mode'] ?? 'offline') === $v) ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Course / Program Offered
            <input type="text" name="course_offered" class="form-input" placeholder="e.g. B.Tech in Computer Science" value="<?php echo htmlspecialchars($edit_college['course_offered'] ?? ''); ?>">
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">Website URL
            <input type="url" name="website_url" class="form-input" placeholder="https://..." value="<?php echo htmlspecialchars($edit_college['website_url'] ?? ''); ?>">
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">Description
            <textarea name="description" class="form-textarea" rows="3" placeholder="Brief description of the institute and its offerings..."><?php echo htmlspecialchars($edit_college['description'] ?? ''); ?></textarea>
          </label>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary"><?php echo $edit_college ? 'Save Changes' : 'Add Institute'; ?></button>
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
    document.getElementById('collegeModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
    <?php if ($edit_college): ?>
    document.getElementById('collegeModal').classList.add('open');
    <?php endif; ?>
  });
  function openModal()  { document.getElementById('collegeModal').classList.add('open'); }
  function closeModal() { document.getElementById('collegeModal').classList.remove('open'); }
  </script>
</body>
</html>
