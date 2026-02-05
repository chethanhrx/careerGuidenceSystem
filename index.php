<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intelligent Career Guidance System | Find Your Perfect Career</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .hero {
      padding: var(--space-3xl) 0;
      text-align: center;
    }
    .hero h1 {
      margin-bottom: var(--space-md);
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .hero p {
      color: var(--color-text-secondary);
      font-size: var(--text-lg);
      max-width: 500px;
      margin: 0 auto var(--space-xl);
      line-height: 1.6;
    }
    .hero-cta {
      display: flex;
      flex-wrap: wrap;
      gap: var(--space-md);
      justify-content: center;
      margin-bottom: var(--space-2xl);
    }
    .hero-cta .btn {
      min-width: 160px;
    }
    .features {
      padding: var(--space-3xl) 0;
    }
    .features h2 {
      text-align: center;
      margin-bottom: var(--space-2xl);
    }
    .features-grid {
      display: grid;
      gap: var(--space-lg);
      grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
      .features-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
      .features-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .feature-card {
      padding: var(--space-xl);
      text-align: center;
    }
    .feature-card .icon {
      width: 56px;
      height: 56px;
      margin: 0 auto var(--space-md);
      background: var(--color-primary-light);
      color: var(--color-primary);
      border-radius: var(--radius-xl);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
    .feature-card h3 {
      margin-bottom: var(--space-sm);
    }
    .feature-card p {
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
    }
    .footer {
      padding: var(--space-xl) 0;
      border-top: 1px solid var(--color-border);
      text-align: center;
      color: var(--color-text-muted);
      font-size: var(--text-sm);
    }
    .footer a {
      color: var(--color-primary);
    }
    /* About & Instructions sections */
    .about-section {
      padding: var(--space-3xl) 0;
      background: var(--color-surface);
    }
    .about-section .container {
      max-width: 720px;
      margin: 0 auto;
    }
    .about-section h2 {
      text-align: center;
      margin-bottom: var(--space-lg);
    }
    .about-section p {
      color: var(--color-text-secondary);
      line-height: 1.7;
      margin-bottom: var(--space-md);
    }
    .about-section ul {
      padding-left: var(--space-xl);
      margin-bottom: var(--space-md);
      color: var(--color-text-secondary);
      line-height: 1.7;
    }
    .about-section li {
      margin-bottom: var(--space-xs);
    }
    .how-section {
      padding: var(--space-3xl) 0;
    }
    .how-section h2 {
      text-align: center;
      margin-bottom: var(--space-2xl);
    }
    .steps {
      display: grid;
      gap: var(--space-lg);
      grid-template-columns: 1fr;
      max-width: 560px;
      margin: 0 auto;
    }
    @media (min-width: 768px) {
      .steps { grid-template-columns: repeat(2, 1fr); max-width: 100%; }
    }
    @media (min-width: 1024px) {
      .steps { grid-template-columns: repeat(4, 1fr); }
    }
    .step-card {
      padding: var(--space-xl);
      text-align: center;
      position: relative;
    }
    .step-card .step-num {
      width: 40px;
      height: 40px;
      margin: 0 auto var(--space-md);
      background: var(--color-primary);
      color: #fff;
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: var(--text-lg);
    }
    .step-card h3 {
      font-size: var(--text-base);
      margin-bottom: var(--space-sm);
    }
    .step-card p {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      line-height: 1.5;
    }
    .who-section {
      padding: var(--space-3xl) 0;
      background: var(--color-surface);
    }
    .who-section h2 {
      text-align: center;
      margin-bottom: var(--space-2xl);
    }
    .who-grid {
      display: grid;
      gap: var(--space-lg);
      grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
      .who-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
      .who-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .who-card {
      padding: var(--space-xl);
      text-align: center;
    }
    .who-card .icon {
      font-size: 2rem;
      margin-bottom: var(--space-md);
    }
    .who-card h3 {
      font-size: var(--text-lg);
      margin-bottom: var(--space-sm);
    }
    .who-card p {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      line-height: 1.5;
    }
    .cta-section {
      padding: var(--space-3xl) 0;
      text-align: center;
    }
    .cta-section h2 {
      margin-bottom: var(--space-md);
    }
    .cta-section p {
      color: var(--color-text-secondary);
      margin-bottom: var(--space-xl);
      max-width: 480px;
      margin-left: auto;
      margin-right: auto;
    }
    .cta-section .btn {
      min-width: 200px;
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
    .page-loader .skeleton-title { width: 200px; height: 32px; margin: 0 auto var(--space-md); }
    .page-loader .skeleton-text { width: 280px; height: 20px; margin: 0 auto var(--space-sm); }
  </style>
</head>
<body>
  <!-- Page load skeleton -->
  <div class="page-loader" id="pageLoader">
    <div class="text-center">
      <div class="skeleton skeleton-title"></div>
      <div class="skeleton skeleton-text"></div>
      <div class="skeleton skeleton-text"></div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <div class="header-left">
        <a href="index.php" class="logo">Career<span>Guide</span></a>
        <nav class="nav-links">
          <a href="index.php" class="active">Home</a>
          <a href="index.php#how-it-works">How it works</a>
          <a href="index.php#who">Who it's for</a>
          <a href="test-results.php">Results</a>
        </nav>
      </div>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon sr-only" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
        <a href="login.php" class="btn btn-ghost">Login</a>
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <button class="hamburger" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
  <nav class="mobile-nav" id="mobileNav">
    <a href="index.php" class="active">Home</a>
    <a href="index.php#how-it-works">How it works</a>
    <a href="index.php#who">Who it's for</a>
    <a href="test-results.php">Results</a>
    <a href="login.php">Login</a>
    <a href="register.php">Get Started</a>
  </nav>

  <main>
    <section class="hero">
      <div class="container">
        <h1>Discover Your Ideal Career Path</h1>
        <p>Take a smart assessment, get personalized career recommendations, and build a roadmap to success.</p>
        <div class="hero-cta">
          <a href="register.php" class="btn btn-primary">Start Free Assessment</a>
          <a href="login.php" class="btn btn-secondary">Sign In</a>
        </div>
      </div>
    </section>

    <section class="features">
      <div class="container">
        <h2>Why Choose CareerGuide?</h2>
        <div class="features-grid">
          <div class="card feature-card">
            <div class="icon">📊</div>
            <h3>Smart Assessment</h3>
            <p>MCQ-based aptitude, interest, and skill tests that map to real careers.</p>
          </div>
          <div class="card feature-card">
            <div class="icon">🎯</div>
            <h3>Personalized Recommendations</h3>
            <p>Get top 3–5 career matches with match percentages and insights.</p>
          </div>
          <div class="card feature-card">
            <div class="icon">🗺️</div>
            <h3>Learning Roadmaps</h3>
            <p>Step-by-step paths, courses, and certifications for each career.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- About Career Guidance -->
    <section class="about-section">
      <div class="container">
        <h2>What Is Career Guidance?</h2>
        <p>Career guidance helps you understand your strengths, interests, and skills so you can choose a career that fits you. Instead of guessing or following others, you get data-driven suggestions based on your own answers.</p>
        <p><strong>Why it matters:</strong></p>
        <ul>
          <li>Reduces confusion after 10th, 12th, or graduation about which stream or job to choose.</li>
          <li>Matches your personality and interests to real job roles and industries.</li>
          <li>Shows you the skills, courses, and certifications needed for each career.</li>
          <li>Gives you a clear learning roadmap so you know what to do next.</li>
        </ul>
        <p>CareerGuide uses a short assessment (aptitude, interest, and skill questions) to recommend the best careers for you and then shows salary ranges, job roles, and step-by-step learning paths.</p>
      </div>
    </section>

    <!-- How It Works / Instructions -->
    <section class="how-section" id="how-it-works">
      <div class="container">
        <h2>How It Works — Step by Step</h2>
        <p class="text-center text-muted" style="margin-bottom: var(--space-2xl); max-width: 560px; margin-left: auto; margin-right: auto;">Follow these simple steps to get your personalized career report.</p>
        <div class="steps">
          <div class="card step-card">
            <div class="step-num">1</div>
            <h3>Register</h3>
            <p>Create a free account with your name and email. No payment required.</p>
          </div>
          <div class="card step-card">
            <div class="step-num">2</div>
            <h3>Complete profile</h3>
            <p>Tell us your education level, stream, skills, interests, and career goal (job, higher studies, or entrepreneurship).</p>
          </div>
          <div class="card step-card">
            <div class="step-num">3</div>
            <h3>Take the assessment</h3>
            <p>Answer 10 multiple-choice questions on aptitude, interests, and skills. Takes about 10–15 minutes. Be honest — there are no wrong answers.</p>
          </div>
          <div class="card step-card">
            <div class="step-num">4</div>
            <h3>Get recommendations</h3>
            <p>View your top 3–5 career matches with match percentages. Open any career to see salary, skills, roadmap, courses, and certifications.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Who Is This For -->
    <section class="who-section" id="who">
      <div class="container">
        <h2>Who Is CareerGuide For?</h2>
        <div class="who-grid">
          <div class="card who-card">
            <div class="icon">🎓</div>
            <h3>Students after 10th</h3>
            <p>Choosing Science, Commerce, or Arts? Get clarity on which stream and future careers suit you best.</p>
          </div>
          <div class="card who-card">
            <div class="icon">📚</div>
            <h3>Students after 12th</h3>
            <p>Deciding between degree, diploma, or professional courses? See which careers match your interests and get a learning path.</p>
          </div>
          <div class="card who-card">
            <div class="icon">💼</div>
            <h3>Graduates & career changers</h3>
            <p>Already graduated or thinking of switching? Get recommendations and a roadmap to upskill and move into a new role.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="cta-section">
      <div class="container">
        <h2>Ready to Find Your Career Path?</h2>
        <p>Join thousands of students and professionals who used CareerGuide to make informed decisions. Start with a free account and get your recommendations in under 20 minutes.</p>
        <div class="hero-cta">
          <a href="register.php" class="btn btn-primary">Start Free Assessment</a>
          <a href="login.php" class="btn btn-secondary">Sign In</a>
        </div>
      </div>
    </section>

    <footer class="footer">
      <div class="container">
        <p>&copy; 2025 CareerGuide. Intelligent Career Guidance System. <a href="admin/login.php">Admin</a></p>
      </div>
    </footer>
  </main>

  <script>
    (function() {
      // Theme: load saved preference
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

      // Page loader: hide after content ready
      var loader = document.getElementById('pageLoader');
      if (loader) {
        window.addEventListener('load', function() {
          setTimeout(function() {
            loader.classList.add('hidden');
          }, 400);
        });
      }
    })();
  </script>
</body>
</html>
