<aside class="sidebar">
  <h3>Student Panel</h3>
  <?php
    $studentHasVoted = false;
    if (isset($_SESSION['student_id']) && isset($conn) && database_ready($conn)) {
        $studentHasVoted = db_count_votes_for_student((int) $_SESSION['student_id']) > 0;
    }
  ?>
  <nav class="accordion-nav">
    <?php $current = basename($_SERVER['PHP_SELF']); ?>

    <div class="nav-item">
      <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <span class="icon">&#127968;</span> Dashboard
      </a>
    </div>

    <div class="nav-item">
      <?php if (!$studentHasVoted): ?>
        <a href="vote.php" class="nav-link <?= $current === 'vote.php' ? 'active' : '' ?>">
          <span class="icon">&#128499;</span> Vote
        </a>
      <?php else: ?>
        <button class="nav-toggle active" onclick="toggleMenu(this)">
          <span class="icon">&#128499;</span> Voting
          <span class="arrow">&#9660;</span>
        </button>
        <div class="submenu" style="display: block;">
          <span class="submenu-link" style="padding: 10px 12px 10px 40px; color: #28a745; cursor: default;">&#10003; Voted</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="nav-item">
      <a href="results.php" class="nav-link <?= $current === 'results.php' ? 'active' : '' ?>">
        <span class="icon">&#128202;</span> Results
      </a>
    </div>

    <div class="nav-item" style="margin-top: 20px;">
      <a href="../backend/logout.php" class="nav-link logout-link">
        <span class="icon">&#128682;</span> Logout
      </a>
    </div>
  </nav>

  <script>
  function toggleMenu(button) {
    const submenu = button.nextElementSibling;
    const allMenus = document.querySelectorAll('.accordion-nav .submenu');
    const allButtons = document.querySelectorAll('.accordion-nav .nav-toggle');

    allMenus.forEach(menu => {
      if (menu !== submenu) {
        menu.style.display = 'none';
      }
    });

    allButtons.forEach(btn => {
      if (btn !== button) {
        btn.classList.remove('active');
      }
    });

    submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
    button.classList.toggle('active');
  }
  </script>

  <style>
  .accordion-nav {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .nav-item {
    margin-bottom: 8px;
  }

  .nav-link, .nav-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 14px;
    background-color: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 12px;
    text-decoration: none;
    color: rgba(255, 255, 255, 0.92);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
  }

  .nav-toggle {
    justify-content: space-between;
  }

  .nav-link:hover, .nav-toggle:hover {
    background-color: rgba(255, 255, 255, 0.17);
    color: rgba(255, 255, 255, 0.98);
  }

  .nav-link.active, .nav-toggle.active {
    background-color: rgba(255, 255, 255, 0.92);
    color: #0f1a2b;
    border-color: rgba(255, 255, 255, 0.85);
  }

  .nav-toggle.active .arrow {
    transform: rotate(180deg);
    transition: transform 0.3s ease;
  }

  .arrow {
    font-size: 12px;
    transition: transform 0.3s ease;
  }

  .icon {
    font-size: 18px;
    min-width: 24px;
  }

  .submenu {
    display: none;
    flex-direction: column;
    background-color: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-top: none;
    border-radius: 0 0 12px 12px;
    overflow: hidden;
    margin-top: -8px;
  }

  .submenu-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px 10px 40px;
    background-color: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.82);
    text-decoration: none;
    font-size: 13px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.2s ease;
  }

  .submenu-link:last-child {
    border-bottom: none;
  }

  .submenu-link:hover {
    background-color: rgba(255, 255, 255, 0.18);
    color: rgba(255, 255, 255, 0.98);
    padding-left: 45px;
  }

  .submenu-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.98);
    font-weight: 600;
  }

  .logout-link {
    justify-content: center;
    background-color: #dc3545;
    color: white;
    border-color: rgba(255, 255, 255, 0.28);
  }

  .logout-link:hover {
    background-color: #c82333;
    color: white;
  }
  </style>
</aside>
