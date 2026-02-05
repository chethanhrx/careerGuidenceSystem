<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user profile
$user_profile = [];
$profile_sql = "SELECT * FROM user_profiles WHERE user_id = '$user_id'";
$profile_result = mysqli_query($conn, $profile_sql);
if ($profile_result && mysqli_num_rows($profile_result) > 0) {
    $user_profile = mysqli_fetch_assoc($profile_result);
    $user_profile['skills'] = json_decode($user_profile['skills'] ?? '[]', true) ?: [];
    $user_profile['interests'] = json_decode($user_profile['interests'] ?? '[]', true) ?: [];
}

// Get all tests taken by user
$tests = [];
$sql_tests = "SELECT * FROM user_tests WHERE user_id = '$user_id' ORDER BY completed_at DESC";
$result_tests = mysqli_query($conn, $sql_tests);

if ($result_tests && mysqli_num_rows($result_tests) > 0) {
    while ($row = mysqli_fetch_assoc($result_tests)) {
        // Decode results JSON
        $row['results'] = json_decode($row['results'], true);
        
        // Extract basic info
        $row['overall_score'] = $row['results']['overall_score'] ?? 0;
        $row['top_career'] = '';
        $row['top_score'] = 0;
        
        if (isset($row['results']['scores']) && is_array($row['results']['scores'])) {
            arsort($row['results']['scores']);
            $top_career_slug = array_key_first($row['results']['scores']);
            $row['top_score'] = $row['results']['scores'][$top_career_slug] ?? 0;
            
            // Get career title
            $career_sql = "SELECT title FROM careers WHERE slug = '$top_career_slug'";
            $career_result = mysqli_query($conn, $career_sql);
            if ($career_result && mysqli_num_rows($career_result) > 0) {
                $career_row = mysqli_fetch_assoc($career_result);
                $row['top_career'] = $career_row['title'];
            }
        }
        
        $tests[] = $row;
    }
}

// Get latest test for detailed view
$latest_test = !empty($tests) ? $tests[0] : null;
$career_scores = $latest_test['results']['scores'] ?? [];
$suggested_careers_slugs = $latest_test['results']['suggested_careers'] ?? [];

// Get detailed info for suggested careers
$suggested_careers = [];
if (!empty($suggested_careers_slugs)) {
    foreach ($suggested_careers_slugs as $career_slug) {
        $career_sql = "SELECT * FROM careers WHERE slug = '$career_slug'";
        $career_result = mysqli_query($conn, $career_sql);
        if ($career_result && mysqli_num_rows($career_result) > 0) {
            // FIXED: Changed $career_data to $career_row
            $career_row = mysqli_fetch_assoc($career_result);
            $career_row['score'] = $career_scores[$career_slug] ?? 0;
            
            // Decode JSON fields
            $career_row['skills'] = json_decode($career_row['skills'] ?? '[]', true) ?: [];
            $career_row['roles'] = json_decode($career_row['roles'] ?? '[]', true) ?: [];
            $career_row['roadmap'] = json_decode($career_row['roadmap'] ?? '[]', true) ?: [];
            $career_row['courses'] = json_decode($career_row['courses'] ?? '[]', true) ?: [];
            $career_row['certs'] = json_decode($career_row['certs'] ?? '[]', true) ?: [];
            
            $suggested_careers[] = $career_row;
        }
    }
}

// Calculate stats
$total_tests = count($tests);
$avg_score = 0;
$highest_score = 0;
$test_dates = [];

if ($total_tests > 0) {
    $total_score = 0;
    foreach ($tests as $test) {
        $total_score += $test['overall_score'];
        if ($test['overall_score'] > $highest_score) {
            $highest_score = $test['overall_score'];
        }
        $test_dates[] = date('d M', strtotime($test['completed_at']));
    }
    $avg_score = round($total_score / $total_tests, 1);
}

// Get skill development recommendations
$skill_recommendations = [];
if (!empty($user_profile['skills']) && !empty($latest_test)) {
    $user_skills = $user_profile['skills'];
    
    // For each suggested career, find missing skills
    foreach ($suggested_careers as $career) {
        $career_skills = $career['skills'] ?? [];
        $missing_skills = array_diff($career_skills, $user_skills);
        
        if (!empty($missing_skills)) {
            $skill_recommendations[] = [
                'career' => $career['title'],
                'missing_skills' => array_slice($missing_skills, 0, 3), // Top 3 missing skills
                'score' => $career['score'] ?? 0
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results & Analysis | CareerGuide</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        /* Light theme colors */
        --color-bg: #ffffff;
        --color-bg-light: #f7fafc;
        --color-text-primary: #2d3748;
        --color-text-secondary: #4a5568;
        --color-text-muted: #718096;
        --color-border: #e2e8f0;
        --color-primary: #4f46e5;
        --color-primary-light: #e0e7ff;
        --color-primary-dark: #3730a3;
        --color-secondary: #6b7280;
        --color-secondary-light: #f3f4f6;
    }
    
    [data-theme="dark"] {
        /* Dark theme colors */
        --color-bg: #1a202c;
        --color-bg-light: #2d3748;
        --color-text-primary: #f7fafc;
        --color-text-secondary: #e2e8f0;
        --color-text-muted: #a0aec0;
        --color-border: #4a5568;
        --color-primary: #818cf8;
        --color-primary-light: #3730a3;
        --color-primary-dark: #6366f1;
        --color-secondary: #9ca3af;
        --color-secondary-light: #374151;
    }
    
    .chart-container {
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        position: relative;
    }
    
    .chart-container h2 {
        margin-bottom: var(--space-lg);
        font-size: var(--text-lg);
        color: var(--color-text-primary);
    }
    
    /* Rest of your existing CSS remains the same but will use CSS variables */
    .results-page {
        padding: var(--space-lg);
        max-width: 1200px;
        margin: 0 auto;
        background: var(--color-bg);
        color: var(--color-text-primary);
    }
    
    .results-header {
        margin-bottom: var(--space-xl);
    }
    
    .results-header h1 {
        color: var(--color-text-primary);
    }
    
    .results-header .text-muted {
        color: var(--color-text-muted);
    }
    
    .stat-card {
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
        display: flex;
        flex-direction: column;
    }
    
    .stat-card h3 {
        font-size: var(--text-sm);
        color: var(--color-text-secondary);
        margin-bottom: var(--space-sm);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .stat-card .value {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: var(--color-text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .stat-card .description {
        font-size: var(--text-sm);
        color: var(--color-text-muted);
    }
    
    .test-history {
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .test-history h2 {
        color: var(--color-text-primary);
    }
    
    .test-history table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .test-history th {
        text-align: left;
        padding: var(--space-md);
        border-bottom: 2px solid var(--color-border);
        color: var(--color-text-secondary);
        font-weight: 600;
    }
    
    .test-history td {
        padding: var(--space-md);
        border-bottom: 1px solid var(--color-border);
        color: var(--color-text-primary);
    }
    
    .test-history tr:hover {
        background: var(--color-bg-light);
    }
    
    .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: var(--text-sm);
        font-weight: 600;
    }
    
    .score-badge.high {
        background: #d4edda;
        color: #155724;
    }
    
    .score-badge.medium {
        background: #fff3cd;
        color: #856404;
    }
    
    .score-badge.low {
        background: #f8d7da;
        color: #721c24;
    }
    
    .career-scores {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }
    
    .career-score-card {
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
    }
    
    .career-score-card h3 {
        margin-bottom: var(--space-sm);
        color: var(--color-text-primary);
    }
    
    .progress-bar {
        height: 10px;
        background: var(--color-border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin: var(--space-sm) 0;
    }
    
    .progress-fill {
        height: 100%;
        background: var(--color-primary);
        border-radius: var(--radius-full);
        transition: width 0.3s ease;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: var(--text-sm);
        color: var(--color-text-secondary);
    }
    
    .skill-recommendations {
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .skill-recommendations h2 {
        margin-bottom: var(--space-lg);
        color: var(--color-text-primary);
    }
    
    .recommendation-item {
        padding: var(--space-md);
        border-bottom: 1px solid var(--color-border);
    }
    
    .recommendation-item:last-child {
        border-bottom: none;
    }
    
    .recommendation-item h4 {
        margin-bottom: var(--space-xs);
        color: var(--color-text-primary);
    }
    
    .skill-tag {
        display: inline-block;
        padding: 4px 12px;
        background: var(--color-bg-light);
        color: var(--color-text-secondary);
        border-radius: 20px;
        font-size: var(--text-sm);
        margin-right: var(--space-xs);
        margin-bottom: var(--space-xs);
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-md);
        margin-top: var(--space-xl);
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .career-scores {
            grid-template-columns: 1fr;
        }
        
        .test-history {
            overflow-x: auto;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .chart-container {
            padding: var(--space-md);
        }
        
        .chart-container h2 {
            font-size: var(--text-base);
        }
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
                    <a href="profile-setup.php">Profile</a>
                    <a href="assessment.php">Assessment</a>
                    <a href="test-results.php" class="active">Results</a>
                    <a href="careers.php">Careers</a>
                </nav>
            </div>
            <div class="nav-actions">
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <span style="color: var(--color-text-secondary); margin-right: var(--space-sm);">
                    Hi, <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
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
        <a href="test-results.php" class="active">Results</a>
        <a href="careers.php">Careers</a>
        <a href="./logout.php">Logout</a>
    </nav>

    <main class="results-page">
        <div class="results-header">
            <h1>Test Results & Analysis</h1>
            <p class="text-muted">Track your career assessment progress and get personalized recommendations</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Tests</h3>
                <div class="value"><?php echo $total_tests; ?></div>
                <div class="description">Career assessments completed</div>
            </div>
            <div class="stat-card">
                <h3>Average Score</h3>
                <div class="value"><?php echo $avg_score; ?>%</div>
                <div class="description">Overall compatibility score</div>
            </div>
            <div class="stat-card">
                <h3>Highest Score</h3>
                <div class="value"><?php echo $highest_score; ?>%</div>
                <div class="description">Best assessment performance</div>
            </div>
            <div class="stat-card">
                <h3>Latest Test</h3>
                <div class="value">
                    <?php if ($latest_test): ?>
                        <?php echo date('d M', strtotime($latest_test['completed_at'])); ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
                <div class="description">Date of most recent assessment</div>
            </div>
        </div>

        <?php if ($latest_test): ?>
            <!-- Career Scores Chart -->
            <div class="chart-container">
    <h2>Career Compatibility Scores</h2>
    <div style="position: relative; height: 300px; width: 100%;">
        <canvas id="careerScoresChart"></canvas>
    </div>
</div>

            <!-- Latest Test Scores -->
            <?php if (!empty($career_scores)): ?>
            <div class="career-scores">
                <?php foreach ($career_scores as $career_slug => $score): 
                    $career_name = ucwords(str_replace('-', ' ', $career_slug));
                ?>
                    <div class="career-score-card">
                        <h3><?php echo $career_name; ?></h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $score; ?>%;"></div>
                        </div>
                        <div class="progress-label">
                            <span>Compatibility Score</span>
                            <span><?php echo $score; ?>%</span>
                        </div>
                        <?php if ($score >= 80): ?>
                            <div class="score-badge high">Excellent Match</div>
                        <?php elseif ($score >= 60): ?>
                            <div class="score-badge medium">Good Match</div>
                        <?php else: ?>
                            <div class="score-badge low">Consider Developing Skills</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Skill Recommendations -->
            <?php if (!empty($skill_recommendations)): ?>
            <div class="skill-recommendations">
                <h2>Skill Development Recommendations</h2>
                <?php foreach ($skill_recommendations as $recommendation): ?>
                    <div class="recommendation-item">
                        <h4><?php echo htmlspecialchars($recommendation['career']); ?> (<?php echo $recommendation['score']; ?>% match)</h4>
                        <p style="color: var(--color-text-secondary); margin-bottom: var(--space-sm);">
                            Develop these skills to improve your compatibility:
                        </p>
                        <div>
                            <?php foreach ($recommendation['missing_skills'] as $skill): ?>
                                <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="skill-recommendations">
                    <h2>Skill Development Recommendations</h2>
                    <p class="text-muted" style="text-align: center; padding: var(--space-lg);">
                        No specific skill recommendations available. Complete your profile to get personalized suggestions.
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="chart-container" style="text-align: center;">
                <h2>No Test Results Found</h2>
                <p class="text-muted" style="padding: var(--space-xl);">
                    You haven't taken any career assessments yet. 
                    <a href="assessment.php" style="display: inline-block; margin-top: var(--space-md);" class="btn btn-primary">
                        Take Your First Assessment
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <!-- Test History -->
        <?php if (!empty($tests)): ?>
        <div class="test-history">
            <h2 style="margin-bottom: var(--space-lg);">Test History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Overall Score</th>
                        <th>Top Career Match</th>
                        <th>Top Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): 
                        $score_class = 'low';
                        if ($test['overall_score'] >= 80) $score_class = 'high';
                        elseif ($test['overall_score'] >= 60) $score_class = 'medium';
                    ?>
                        <tr>
                            <td><?php echo date('d M Y, h:i A', strtotime($test['completed_at'])); ?></td>
                            <td>
                                <span class="score-badge <?php echo $score_class; ?>">
                                    <?php echo $test['overall_score']; ?>%
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($test['top_career']); ?></td>
                            <td><?php echo $test['top_score']; ?>%</td>
                            <td>
                                <a href="assessment.php?view_result=<?php echo $test['id']; ?>" 
                                   class="btn btn-secondary btn-sm">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="assessment.php" class="btn btn-primary">Take New Assessment</a>
            <a href="careers.php" class="btn btn-outline">Explore Careers</a>
            <a href="dashboard.php" class="btn btn-ghost">Back to Dashboard</a>
        </div>
    </main>

    <!-- Replace the chart container and JavaScript chart code with this: -->


<script>
    // Theme toggle with chart updates
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
        var btn = document.getElementById('themeToggle');
        var sun = btn && btn.querySelector('.icon-sun');
        var moon = btn && btn.querySelector('.icon-moon');
        
        // Function to update chart colors based on theme
        function updateChartColors(theme) {
            const chart = Chart.getChart('careerScoresChart');
            if (!chart) return;
            
            const isDark = theme === 'dark';
            
            // Update axis colors
            chart.options.scales.x.ticks.color = isDark ? '#a0aec0' : '#4a5568';
            chart.options.scales.y.ticks.color = isDark ? '#a0aec0' : '#4a5568';
            chart.options.scales.y.title.color = isDark ? '#a0aec0' : '#4a5568';
            chart.options.scales.x.grid.color = isDark ? '#2d3748' : '#e2e8f0';
            chart.options.scales.y.grid.color = isDark ? '#2d3748' : '#e2e8f0';
            
            // Update tooltip colors
            chart.options.plugins.tooltip.backgroundColor = isDark ? 'rgba(26, 32, 44, 0.9)' : 'rgba(0, 0, 0, 0.8)';
            
            chart.update();
        }
        
        if (btn) {
            // Set initial icon state
            if (theme === 'dark') { 
                if (sun) sun.classList.add('sr-only'); 
                if (moon) moon.classList.remove('sr-only'); 
            } else { 
                if (sun) sun.classList.remove('sr-only'); 
                if (moon) moon.classList.add('sr-only'); 
            }
            
            btn.addEventListener('click', function() {
                var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                var newTheme = isDark ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Update icons
                if (sun) sun.classList.toggle('sr-only', !isDark);
                if (moon) moon.classList.toggle('sr-only', isDark);
                
                // Update chart colors
                updateChartColors(newTheme);
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
    })();

    // Career Scores Chart with theme support
    <?php if (!empty($career_scores)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('careerScoresChart');
        const ctx = canvas.getContext('2d');
        const careerNames = [];
        const careerScores = [];
        const backgroundColors = [];
        
        <?php foreach ($career_scores as $career_slug => $score): 
            $career_name = ucwords(str_replace('-', ' ', $career_slug));
            // Determine color based on score
            $color = $score >= 80 ? '#28a745' : ($score >= 60 ? '#ffc107' : '#dc3545');
        ?>
            careerNames.push('<?php echo $career_name; ?>');
            careerScores.push(<?php echo $score; ?>);
            backgroundColors.push('<?php echo $color; ?>');
        <?php endforeach; ?>
        
        // Get current theme
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const isDark = currentTheme === 'dark';
        
        // Set canvas dimensions
        canvas.width = canvas.parentElement.offsetWidth;
        canvas.height = 300;
        
        // Create chart with theme-aware colors
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: careerNames,
                datasets: [{
                    label: 'Compatibility Score',
                    data: careerScores,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(c => c),
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y}%`;
                            }
                        },
                        backgroundColor: isDark ? 'rgba(26, 32, 44, 0.9)' : 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 4
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false,
                            color: isDark ? '#2d3748' : '#e2e8f0'
                        },
                        ticks: {
                            color: isDark ? '#a0aec0' : '#4a5568',
                            font: {
                                size: 12
                            },
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: isDark ? '#2d3748' : '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: isDark ? '#a0aec0' : '#4a5568',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return value + '%';
                            },
                            stepSize: 20
                        },
                        title: {
                            display: true,
                            text: 'Compatibility Score (%)',
                            color: isDark ? '#a0aec0' : '#4a5568',
                            font: {
                                size: 12,
                                weight: 'normal'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                canvas.width = canvas.parentElement.offsetWidth;
                chart.resize();
            }, 250);
        });
        
        // Store chart reference for theme updates
        window.careerChart = chart;
    });
    <?php endif; ?>
</script>



</body>
</html>