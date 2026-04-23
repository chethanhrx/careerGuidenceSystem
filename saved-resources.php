<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$pdo = getDBConnection();

// Fetch Saved Careers
$saved_careers = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, uc.saved_at
        FROM careers c
        JOIN user_careers uc ON c.id = uc.career_id
        WHERE uc.user_id = :user_id
        ORDER BY uc.saved_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $saved_careers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Saved careers fetch error: " . $e->getMessage());
}

// Fetch Saved Courses
$saved_courses = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, usr.saved_at 
        FROM courses c
        JOIN user_saved_resources usr ON c.id = usr.resource_id
        WHERE usr.user_id = :user_id AND usr.resource_type = 'course'
        ORDER BY usr.saved_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $saved_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Saved courses fetch error: " . $e->getMessage());
}

// Fetch Saved Colleges
$saved_colleges = [];
try {
    $stmt = $pdo->prepare("
        SELECT cl.*, usr.saved_at 
        FROM colleges cl
        JOIN user_saved_resources usr ON cl.id = usr.resource_id
        WHERE usr.user_id = :user_id AND usr.resource_type = 'college'
        ORDER BY usr.saved_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $saved_colleges = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Saved colleges fetch error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Resources | CareerGuide</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .saved-page {
      padding: 40px 20px;
      max-width: 1100px;
      margin: 60px auto 0;
    }

    .section-header {
      margin-bottom: 40px;
      text-align: center;
    }

    .section-header h1 {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .resource-section {
      margin-bottom: 60px;
    }

    .resource-section h2 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--color-border, #e0e0e0);
    }

    .resource-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 25px;
    }

    .resource-card {
      background: var(--color-surface, #fff);
      border-radius: 16px;
      border: 1px solid var(--color-border, #e0e0e0);
      padding: 25px;
      display: flex;
      flex-direction: column;
      position: relative;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .resource-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .card-tag {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 15px;
      width: fit-content;
    }

    .career-tag { background: #fef3c7; color: #92400e; }
    .online-tag { background: #dbeafe; color: #1e40af; }
    .offline-tag { background: #d1fae5; color: #065f46; }

    .remove-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: #fee2e2;
      color: #ef4444;
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .remove-btn:hover {
      background: #ef4444;
      color: #fff;
      transform: rotate(90deg);
    }

    .empty-state {
      text-align: center;
      padding: 40px;
      background: #f8fafc;
      border-radius: 12px;
      color: #64748b;
      grid-column: 1 / -1;
    }

    .cta-btn {
      display: block;
      width: 100%;
      text-align: center;
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      margin-top: 20px;
      transition: background 0.2s;
    }

    .career-btn { background: #f59e0b; color: white; }
    .career-btn:hover { background: #d97706; }
    
    .online-btn { background: #3b82f6; color: white; }
    .online-btn:hover { background: #2563eb; }
    
    .offline-btn { background: #10b981; color: white; }
    .offline-btn:hover { background: #059669; }

    .resource-card h4 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      padding-right: 30px;
    }

    .resource-card .meta {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 15px;
    }

    .resource-card .desc {
      font-size: 14px;
      color: #475569;
      line-height: 1.5;
      flex-grow: 1;
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="dashboard.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="recommendations.php">References</a>
          <a href="saved-resources.php" class="active">Saved</a>
        </nav>
      </div>
      <div class="nav-actions">
        <a href="./logout.php" class="btn btn-ghost">Logout</a>
      </div>
    </div>
  </header>

  <main class="saved-page">
    <div class="section-header">
      <h1>My Saved Resources</h1>
      <p>Your personalized collection of careers, courses, and institutes.</p>
    </div>

    <!-- Saved Careers -->
    <section class="resource-section">
      <h2>🎯 Saved Careers</h2>
      <div class="resource-grid">
        <?php foreach ($saved_careers as $career): ?>
          <div class="resource-card" id="career-<?php echo $career['id']; ?>">
            <button class="remove-btn" onclick="removeResource('career', <?php echo $career['id']; ?>)" title="Remove">✕</button>
            <span class="card-tag career-tag">Career</span>
            <h4><?php echo htmlspecialchars($career['title']); ?></h4>
            <div class="meta">📂 <?php echo htmlspecialchars($career['category']); ?></div>
            <p class="desc"><?php echo htmlspecialchars(substr($career['overview'], 0, 120)); ?>...</p>
            <a href="career-path.php?slug=<?php echo urlencode($career['slug']); ?>" class="cta-btn career-btn">View Roadmap</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($saved_careers)): ?>
          <div class="empty-state">No careers bookmarked yet. <a href="career-details.php" style="color:var(--color-primary);">Explore careers</a></div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Saved Courses -->
    <section class="resource-section">
      <h2>🌐 Saved Courses</h2>
      <div class="resource-grid">
        <?php foreach ($saved_courses as $course): ?>
          <div class="resource-card" id="course-<?php echo $course['id']; ?>">
            <button class="remove-btn" onclick="removeResource('course', <?php echo $course['id']; ?>)" title="Remove">✕</button>
            <span class="card-tag online-tag">Online Course</span>
            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
            <div class="meta">🏫 <?php echo htmlspecialchars($course['platform']); ?></div>
            <p class="desc"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 120)); ?>...</p>
            <a href="<?php echo htmlspecialchars($course['url']); ?>" target="_blank" class="cta-btn online-btn">Go to Course</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($saved_courses)): ?>
          <div class="empty-state">No courses saved yet. <a href="recommendations.php" style="color:var(--color-primary);">View references</a></div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Saved Colleges -->
    <section class="resource-section">
      <h2>🏛️ Saved Institutes</h2>
      <div class="resource-grid">
        <?php foreach ($saved_colleges as $college): ?>
          <div class="resource-card" id="college-<?php echo $college['id']; ?>">
            <button class="remove-btn" onclick="removeResource('college', <?php echo $college['id']; ?>)" title="Remove">✕</button>
            <span class="card-tag offline-tag"><?php echo ucfirst($college['type']); ?></span>
            <h4><?php echo htmlspecialchars($college['name']); ?></h4>
            <div class="meta">📍 <?php echo htmlspecialchars($college['location']); ?></div>
            <p class="desc"><?php echo htmlspecialchars(substr($college['description'] ?? '', 0, 120)); ?>...</p>
            <a href="<?php echo htmlspecialchars($college['website_url'] ?? '#'); ?>" target="_blank" class="cta-btn offline-btn">Visit Website</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($saved_colleges)): ?>
          <div class="empty-state">No institutes saved yet.</div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script>
    async function removeResource(type, id) {
      if (!confirm('Are you sure you want to remove this from your saved resources?')) return;
      
      const formData = new FormData();
      formData.append('type', type);
      formData.append('id', id);
      formData.append('csrf', '<?php echo $csrf_token; ?>');

      try {
        const response = await fetch('api/save-resource.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.success && !result.saved) {
          const el = document.getElementById(`${type}-${id}`);
          el.style.opacity = '0';
          el.style.transform = 'scale(0.9)';
          setTimeout(() => {
              el.remove();
              // Check if section is now empty
              const grid = document.querySelector(`#${type}-${id}`)?.parentElement; // already removed
              // (More robust: check all grids)
              document.querySelectorAll('.resource-grid').forEach(g => {
                  if (g.children.length === 0) {
                      g.innerHTML = '<div class="empty-state">No items left in this section.</div>';
                  }
              });
          }, 300);
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }
  </script>
</body>
</html>
