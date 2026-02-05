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

// Get all careers for career_relevance field
$careers = [];
$career_query = "SELECT id, title FROM careers ORDER BY title ASC";
$career_result = mysqli_query($conn, $career_query);
if ($career_result) {
    while ($row = mysqli_fetch_assoc($career_result)) {
        $careers[] = $row;
    }
}

// Handle form submissions (Add/Edit Question)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $question_text = mysqli_real_escape_string($conn, trim($_POST['question_text']));
    $question_type = mysqli_real_escape_string($conn, trim($_POST['question_type']));
    $weight = isset($_POST['weight']) ? intval($_POST['weight']) : 1;
    $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
    
    // Process options (comma-separated to JSON)
    $options_raw = isset($_POST['options']) ? trim($_POST['options']) : '';
    $options_array = array_map('trim', explode(',', $options_raw));
    $options_json = json_encode(array_filter($options_array));
    
    // Process correct_options (comma-separated to JSON)
    $correct_options_raw = isset($_POST['correct_options']) ? trim($_POST['correct_options']) : '';
    $correct_options_array = array_map('trim', explode(',', $correct_options_raw));
    $correct_options_json = !empty($correct_options_raw) ? json_encode(array_filter($correct_options_array)) : 'null';
    
    // Process related_skills (comma-separated to JSON)
    $related_skills_raw = isset($_POST['related_skills']) ? trim($_POST['related_skills']) : '';
    $related_skills_array = array_map('trim', explode(',', $related_skills_raw));
    $related_skills_json = !empty($related_skills_raw) ? json_encode(array_filter($related_skills_array)) : 'null';
    
    // Process career_relevance (comma-separated IDs to JSON)
    $career_relevance_raw = isset($_POST['career_relevance']) ? trim($_POST['career_relevance']) : '';
    $career_relevance_array = array_map('intval', array_filter(explode(',', $career_relevance_raw)));
    $career_relevance_json = !empty($career_relevance_array) ? json_encode($career_relevance_array) : 'null';
    
    if (empty($question_text) || empty($options_raw)) {
        $message = 'Question text and options are required';
        $message_type = 'error';
    } else {
        if ($id > 0) {
            // Update existing question
            $sql = "UPDATE assessment_questions SET 
                    question_text = '$question_text',
                    question_type = '$question_type',
                    options = '$options_json',
                    correct_options = $correct_options_json,
                    related_skills = $related_skills_json,
                    weight = $weight,
                    career_relevance = $career_relevance_json,
                    sort_order = $sort_order,
                    created_at = created_at
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: questions.php?success=updated");
                exit();
            } else {
                $message = 'Error updating question: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        } else {
            // Insert new question
            $sql = "INSERT INTO assessment_questions 
                    (question_text, question_type, options, correct_options, related_skills, weight, career_relevance, sort_order, created_at) 
                    VALUES ('$question_text', '$question_type', '$options_json', $correct_options_json, $related_skills_json, $weight, $career_relevance_json, $sort_order, NOW())";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: questions.php?success=added");
                exit();
            } else {
                $message = 'Error adding question: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Handle success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $message = 'Question added successfully';
        $message_type = 'success';
    } elseif ($_GET['success'] == 'updated') {
        $message = 'Question updated successfully';
        $message_type = 'success';
    } elseif ($_GET['success'] == 'deleted') {
        $message = 'Question deleted successfully';
        $message_type = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $sql = "DELETE FROM assessment_questions WHERE id = $delete_id";
    if (mysqli_query($conn, $sql)) {
        header("Location: questions.php?success=deleted");
        exit();
    } else {
        $message = 'Error deleting question: ' . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Get all questions
$sql = "SELECT * FROM assessment_questions ORDER BY sort_order ASC, created_at DESC";
$result = mysqli_query($conn, $sql);
$questions = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode JSON fields
        $row['options'] = json_decode($row['options'] ?? '[]', true) ?: [];
        $row['correct_options'] = json_decode($row['correct_options'] ?? '[]', true) ?: [];
        $row['related_skills'] = json_decode($row['related_skills'] ?? '[]', true) ?: [];
        $row['career_relevance'] = json_decode($row['career_relevance'] ?? '[]', true) ?: [];
        $questions[] = $row;
    }
}

// Get question for editing
$edit_question = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM assessment_questions WHERE id = $edit_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_question = mysqli_fetch_assoc($result);
        $edit_question['options'] = json_decode($edit_question['options'] ?? '[]', true) ?: [];
        $edit_question['correct_options'] = json_decode($edit_question['correct_options'] ?? '[]', true) ?: [];
        $edit_question['related_skills'] = json_decode($edit_question['related_skills'] ?? '[]', true) ?: [];
        $edit_question['career_relevance'] = json_decode($edit_question['career_relevance'] ?? '[]', true) ?: [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Questions | CareerGuide Admin</title>
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
    .question-list { 
      display: grid; 
      gap: var(--space-md); 
      margin-top: var(--space-lg);
    }
    .question-item { 
      display: flex; 
      align-items: flex-start; 
      justify-content: space-between; 
      padding: var(--space-lg); 
      border: 1px solid var(--color-border); 
      border-radius: var(--radius-lg); 
      flex-wrap: wrap; 
      gap: var(--space-md);
      background: var(--color-bg);
      transition: all 0.2s ease;
    }
    .question-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    .question-item-content {
      flex: 1;
      min-width: 300px;
    }
    .question-item h3 { 
      font-size: var(--text-base); 
      margin-bottom: var(--space-xs); 
      color: var(--color-text-primary);
    }
    .question-item .meta { 
      font-size: var(--text-sm); 
      color: var(--color-text-secondary);
      margin-bottom: var(--space-sm);
      display: flex;
      flex-wrap: wrap;
      gap: var(--space-md);
    }
    .question-item .options { 
      font-size: var(--text-sm); 
      color: var(--color-text-secondary);
      margin-bottom: var(--space-sm);
      line-height: 1.5;
    }
    .question-actions { 
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
    .tag {
      display: inline-block;
      padding: 2px 8px;
      background: var(--color-bg-light);
      color: var(--color-text-secondary);
      border-radius: 12px;
      font-size: var(--text-xs);
      margin-right: 4px;
      margin-bottom: 4px;
    }
    .tag.type-skills { background: #e3f2fd; color: #1565c0; }
    .tag.type-interests { background: #f3e5f5; color: #7b1fa2; }
    .tag.type-personality { background: #e8f5e9; color: #2e7d32; }
    .tag.type-work_preference { background: #fff3e0; color: #ef6c00; }
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
    .career-select-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: var(--space-sm);
      margin-top: var(--space-sm);
      max-height: 200px;
      overflow-y: auto;
      padding: var(--space-sm);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
    }
    .career-select-item {
      display: flex;
      align-items: center;
      gap: var(--space-xs);
    }
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
      .career-select-grid {
        grid-template-columns: 1fr;
      }
    }
    .add-question-btn {
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
          <a href="careers.php">Careers</a>
          <a href="questions.php" class="active">Questions</a>
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
        <a href="../logout.php" class="btn btn-primary">Logout</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
  
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php">Dashboard</a>
    <a href="careers.php">Careers</a>
    <a href="questions.php" class="active">Questions</a>
    <a href="users.php">Users</a>
    <a href="../index.php">View Site</a>
    <a href="../logout.php">Logout</a>
  </nav>

  <div class="admin-header">
    <h1>Manage Assessment Questions</h1>
    <button type="button" class="btn btn-primary add-question-btn" id="addQuestionBtn" onclick="openModal()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add New Question
    </button>
  </div>

  <main class="admin-main">
    <?php if ($message): ?>
      <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($questions)): ?>
      <div class="empty-state">
        <h3>No questions found</h3>
        <p>Start by adding your first assessment question.</p>
        <button type="button" class="btn btn-primary" onclick="openModal()" style="margin-top: var(--space-md);">
          Add First Question
        </button>
      </div>
    <?php else: ?>
      <div class="question-list" id="questionList">
        <?php foreach ($questions as $question): ?>
          <div class="question-item card">
            <div class="question-item-content">
              <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
              
              <div class="meta">
                <span class="tag type-<?php echo $question['question_type']; ?>">
                  <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['question_type']))); ?>
                </span>
                <?php if ($question['weight'] != 1): ?>
                  <span>Weight: <?php echo $question['weight']; ?></span>
                <?php endif; ?>
                <?php if ($question['sort_order'] != 0): ?>
                  <span>Order: <?php echo $question['sort_order']; ?></span>
                <?php endif; ?>
                <span>Created: <?php echo date('d M Y', strtotime($question['created_at'])); ?></span>
              </div>
              
              <?php if (!empty($question['options'])): ?>
                <div class="options">
                  <strong>Options:</strong> 
                  <?php echo htmlspecialchars(implode(', ', $question['options'])); ?>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($question['correct_options'])): ?>
                <div style="margin-bottom: var(--space-sm); font-size: var(--text-xs);">
                  <strong>Correct:</strong> 
                  <?php foreach ($question['correct_options'] as $correct): ?>
                    <span class="tag" style="background: #d4edda; color: #155724;"><?php echo htmlspecialchars($correct); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($question['related_skills'])): ?>
                <div style="margin-bottom: var(--space-sm); font-size: var(--text-xs);">
                  <strong>Skills:</strong> 
                  <?php foreach ($question['related_skills'] as $skill): ?>
                    <span class="tag"><?php echo htmlspecialchars($skill); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($question['career_relevance'])): ?>
                <div style="font-size: var(--text-xs);">
                  <strong>Careers:</strong> 
                  <?php 
                  $career_names = [];
                  foreach ($careers as $career) {
                    if (in_array($career['id'], $question['career_relevance'])) {
                      $career_names[] = htmlspecialchars($career['title']);
                    }
                  }
                  echo !empty($career_names) ? implode(', ', $career_names) : 'N/A';
                  ?>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="question-actions">
              <a href="?edit=<?php echo $question['id']; ?>" class="btn btn-secondary">
                Edit
              </a>
              <a href="?delete=<?php echo $question['id']; ?>" 
                 class="btn btn-ghost" 
                 onclick="return confirm('Are you sure you want to delete this question? This action cannot be undone.')">
                Delete
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Question Modal -->
  <div class="modal <?php echo $edit_question ? 'open' : ''; ?>" id="questionModal">
    <div class="modal-content">
      <h2 id="modalTitle"><?php echo $edit_question ? 'Edit Question' : 'Add New Question'; ?></h2>
      
      <form id="questionForm" method="post" action="">
        <input type="hidden" id="questionId" name="id" value="<?php echo $edit_question['id'] ?? ''; ?>">
        
        <div class="form-group">
          <label class="form-label" for="questionText">Question Text *</label>
          <textarea id="questionText" 
                    name="question_text" 
                    class="form-textarea" 
                    placeholder="Enter the question text..." 
                    rows="3"
                    required><?php echo htmlspecialchars($edit_question['question_text'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="questionType">Question Type *</label>
            <select id="questionType" name="question_type" class="form-select" required>
              <option value="skills" <?php echo ($edit_question['question_type'] ?? '') == 'skills' ? 'selected' : ''; ?>>Skills</option>
              <option value="interests" <?php echo ($edit_question['question_type'] ?? '') == 'interests' ? 'selected' : ''; ?>>Interests</option>
              <option value="personality" <?php echo ($edit_question['question_type'] ?? '') == 'personality' ? 'selected' : ''; ?>>Personality</option>
              <option value="work_preference" <?php echo ($edit_question['question_type'] ?? '') == 'work_preference' ? 'selected' : ''; ?>>Work Preference</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="questionWeight">Weight</label>
            <input type="number" 
                   id="questionWeight" 
                   name="weight" 
                   class="form-input" 
                   min="1" 
                   max="10"
                   value="<?php echo $edit_question['weight'] ?? 1; ?>">
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Importance weight (1-10)</small>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="questionOptions">Options * (comma-separated)</label>
            <textarea id="questionOptions" 
                      name="options" 
                      class="form-textarea" 
                      placeholder="e.g. Option A, Option B, Option C, Option D" 
                      rows="3"
                      required><?php echo htmlspecialchars(implode(', ', $edit_question['options'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Separate options with commas</small>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="questionCorrect">Correct Options (comma-separated)</label>
            <textarea id="questionCorrect" 
                      name="correct_options" 
                      class="form-textarea" 
                      placeholder="e.g. Option A, Option C" 
                      rows="3"><?php echo htmlspecialchars(implode(', ', $edit_question['correct_options'] ?? [])); ?></textarea>
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">For multiple choice questions, leave empty for assessment questions</small>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="questionSkills">Related Skills (comma-separated)</label>
            <textarea id="questionSkills" 
                      name="related_skills" 
                      class="form-textarea" 
                      placeholder="e.g. Problem Solving, Communication, Leadership" 
                      rows="2"><?php echo htmlspecialchars(implode(', ', $edit_question['related_skills'] ?? [])); ?></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="questionOrder">Sort Order</label>
            <input type="number" 
                   id="questionOrder" 
                   name="sort_order" 
                   class="form-input" 
                   value="<?php echo $edit_question['sort_order'] ?? 0; ?>">
            <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Display order (lower numbers first)</small>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Career Relevance</label>
          <div class="career-select-grid" id="careerSelectGrid">
            <?php foreach ($careers as $career): ?>
              <div class="career-select-item">
                <input type="checkbox" 
                       name="career_relevance_check[]" 
                       value="<?php echo $career['id']; ?>"
                       class="career-checkbox"
                       data-id="<?php echo $career['id']; ?>"
                       <?php echo (isset($edit_question['career_relevance']) && in_array($career['id'], $edit_question['career_relevance'])) ? 'checked' : ''; ?>>
                <label><?php echo htmlspecialchars($career['title']); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="careerRelevance" name="career_relevance" value="<?php echo isset($edit_question['career_relevance']) ? implode(',', $edit_question['career_relevance']) : ''; ?>">
          <small style="color: var(--color-text-muted); font-size: var(--text-xs);">Select careers relevant to this question</small>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary"><?php echo $edit_question ? 'Update Question' : 'Add Question'; ?></button>
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

      // Handle career checkbox selection
      var careerCheckboxes = document.querySelectorAll('.career-checkbox');
      var careerRelevanceInput = document.getElementById('careerRelevance');
      
      careerCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
          var selectedIds = [];
          document.querySelectorAll('.career-checkbox:checked').forEach(function(cb) {
            selectedIds.push(cb.value);
          });
          careerRelevanceInput.value = selectedIds.join(',');
        });
      });

      // Close modal on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('questionModal').classList.contains('open')) {
          closeModal();
        }
      });

      // Close modal when clicking outside
      var questionModal = document.getElementById('questionModal');
      if (questionModal) {
        questionModal.addEventListener('click', function(e) {
          if (e.target === this) {
            closeModal();
          }
        });
      }

      // Also add click event to the "Add First Question" button
      var addFirstQuestionBtn = document.querySelector('.empty-state .btn');
      if (addFirstQuestionBtn && addFirstQuestionBtn.textContent.includes('Add First Question')) {
        addFirstQuestionBtn.addEventListener('click', function(e) {
          e.preventDefault();
          openModal();
        });
      }

      // Auto-open modal if editing (from PHP)
      <?php if ($edit_question): ?>
      // Set edit mode
      document.getElementById('modalTitle').textContent = 'Edit Question';
      document.getElementById('questionId').value = '<?php echo $edit_question['id']; ?>';
      // Show modal
      document.getElementById('questionModal').classList.add('open');
      <?php endif; ?>
    });

    // Global functions for modal
    function openModal() {
      // Reset form and modal title for new question
      document.getElementById('modalTitle').textContent = 'Add New Question';
      document.getElementById('questionForm').reset();
      document.getElementById('questionId').value = '';
      document.getElementById('careerRelevance').value = '';
      // Uncheck all career checkboxes
      document.querySelectorAll('.career-checkbox').forEach(function(cb) {
        cb.checked = false;
      });
      // Show modal
      document.getElementById('questionModal').classList.add('open');
    }

    function closeModal() {
      document.getElementById('questionModal').classList.remove('open');
    }
  </script>
</body>
</html>