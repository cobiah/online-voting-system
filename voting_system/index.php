<?php
include __DIR__ . '/backend/db.php';

$elections = [];
$electionsError = null;
$isPresentation = presentation_mode_enabled() && !database_ready($conn);

if ($isPresentation) {
    $elections = presentation_elections();
} else {
    $elections = db_get_active_home_elections();
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Voting System</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/style.css')) ?>">
</head>
<body>
  <header class="site-header">
    <div class="site-brand">
      <img src="<?= htmlspecialchars(app_url('assets/images/logo.png')) ?>" alt="Voting System Logo">
      <div>
        <h1>Secure Voting System</h1>
        <p style="margin:0; font-size:0.85rem; color: rgba(0,0,0,0.6);">Tertiary Institution Elections</p>
      </div>
    </div>

    <nav class="site-nav">
      <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="active">Home</a>
      <a href="<?= htmlspecialchars(app_url('frontend/vote.php')) ?>">Elections</a>
      <a href="#how">How It Works</a>
      <a href="<?= htmlspecialchars(app_url('frontend/results.php')) ?>">Results</a>
      <a href="<?= htmlspecialchars(app_url('frontend/login.php')) ?>" class="button button-secondary">Login</a>
      <a href="<?= htmlspecialchars(app_url('frontend/register.php')) ?>" class="button button-primary">Register</a>
    </nav>
  </header>

  <main class="main">
    <section class="hero">
      <div class="hero-content">
        <div class="hero-copy">
          <h1>Secure &amp; Easy<br>Online Voting</h1>
          <p>Cast your vote in just a few clicks. Our platform makes elections fast, transparent, and accessible for everyone.</p>

          <div class="hero-buttons">
            <a href="<?= htmlspecialchars(app_url('frontend/vote.php')) ?>" class="button button-primary">Vote Now</a>
            <a href="#how" class="button button-secondary">Learn More</a>
          </div>
        </div>

        <div class="hero-illustration" aria-hidden="true">
          <div class="hero-illustration__graphic"></div>
        </div>
      </div>
    </section>

    <section class="features" id="how">
      <div class="section-header">
        <h2>How It Works</h2>
        <p>Our voting system is built to be simple, secure, and transparent — designed for both students and administrators.</p>
      </div>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">🗳️</div>
          <h3>Current Elections</h3>
          <p>View and participate in ongoing elections that are open for voting right now.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🧭</div>
          <h3>How It Works</h3>
          <p>Learn how our voting workflow ensures each vote is recorded securely and transparently.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3>Results &amp; Stats</h3>
          <p>See real-time results and election statistics after votes are submitted.</p>
        </div>
      </div>
    </section>

    <section class="upcoming">
      <div class="section-header upcoming-header">
        <div>
          <h2>Upcoming Elections</h2>
          <p>Make your voice heard in the next election — check the schedules and candidates below.</p>
        </div>
        <a href="<?= htmlspecialchars(app_url('frontend/vote.php')) ?>" class="button button-secondary view-all">View All Elections</a>
      </div>

      <?php if (!empty($electionsError)): ?>
        <div class="alert error">
          <span class="icon">⚠️</span>
          <span><?= htmlspecialchars($electionsError) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($isPresentation): ?>
        <div class="alert info">
          <span class="icon">i</span>
          <span><?= htmlspecialchars(presentation_notice()) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($elections)): ?>
        <div class="election-grid">
          <?php foreach ($elections as $election): ?>
            <div class="election-card">
              <h3><?= htmlspecialchars($election['title']) ?></h3>
              <p class="election-meta">
                <?= htmlspecialchars($election['start_date'] ? date('F j, Y', strtotime($election['start_date'])) : 'TBD') ?>
                &ndash;
                <?= htmlspecialchars($election['end_date'] ? date('F j, Y', strtotime($election['end_date'])) : 'TBD') ?>
              </p>
              <a href="<?= htmlspecialchars(app_url('frontend/vote.php')) ?>" class="button button-primary">Vote Now</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert info">
          <span class="icon">ℹ️</span>
          <span>No upcoming elections have been created yet. Check back soon.</span>
        </div>
      <?php endif; ?>
    </section>

    <section class="why">
      <div class="section-header">
        <h2>Why Choose Our Voting System?</h2>
      </div>

      <div class="why-grid">
        <div class="why-card">
          <div class="why-icon">🔒</div>
          <h3>Secure &amp; Private</h3>
          <p>Your vote is safe and confidential. We protect data with modern hashing and access controls.</p>
        </div>

        <div class="why-card">
          <div class="why-icon">👍</div>
          <h3>Easy to Use</h3>
          <p>A clean, user-friendly interface makes it easy to cast your vote in just a few steps.</p>
        </div>

        <div class="why-card">
          <div class="why-icon">⏱️</div>
          <h3>Real-Time Results</h3>
          <p>Instant access to election outcomes and candidate standings as votes are recorded.</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <p>&copy; 2026 Secure Voting System | Embu University</p>
  </footer>

  <script src="<?= htmlspecialchars(app_url('assets/js/script.js')) ?>"></script>
</body>
</html>
