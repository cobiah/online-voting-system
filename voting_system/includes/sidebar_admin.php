<aside class="sidebar">
  <h3>Admin Panel</h3>
  <nav class="accordion-nav">
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    
    <!-- Dashboard -->
    <div class="nav-item">
      <a href="admin_dashboard.php" class="nav-link <?= $current === 'admin_dashboard.php' ? 'active' : '' ?>">
        <span class="icon">🏠</span> Dashboard
      </a>
    </div>

    <!-- Candidates -->
    <div class="nav-item">
      <button class="nav-toggle <?= $current === 'add_candidate.php' ? 'active' : '' ?>" onclick="toggleMenu(this)">
        <span class="icon">✅</span> Manage Candidates
        <span class="arrow">▼</span>
      </button>
      <div class="submenu" style="display: <?= $current === 'add_candidate.php' ? 'block' : 'none' ?>;">
        <a href="add_candidate.php" class="submenu-link <?= $current === 'add_candidate.php' ? 'active' : '' ?>">Add Candidate</a>
        <a href="add_candidate.php#list" class="submenu-link">View Candidates</a>
      </div>
    </div>

    <!-- Election Control -->
    <div class="nav-item">
      <button class="nav-toggle <?= $current === 'add_election.php' ? 'active' : '' ?>" onclick="toggleMenu(this)">
        <span class="icon">🗳️</span> Election Control
        <span class="arrow">▼</span>
      </button>
      <div class="submenu" style="display: <?= $current === 'add_election.php' ? 'block' : 'none' ?>;">
        <a href="add_election.php" class="submenu-link <?= $current === 'add_election.php' ? 'active' : '' ?>">Manage Elections</a>
        <a href="add_position.php" class="submenu-link">Add Position</a>
      </div>
    </div>

    <!-- Results -->
    <div class="nav-item">
      <button class="nav-toggle <?= $current === 'admin_results.php' ? 'active' : '' ?>" onclick="toggleMenu(this)">
        <span class="icon">📊</span> Results
        <span class="arrow">▼</span>
      </button>
      <div class="submenu" style="display: <?= $current === 'admin_results.php' ? 'block' : 'none' ?>;">
        <a href="admin_results.php" class="submenu-link <?= $current === 'admin_results.php' ? 'active' : '' ?>">View Results</a>
      </div>
    </div>

    <!-- Voter Management -->
    <div class="nav-item">
      <button class="nav-toggle <?= $current === 'voter_management.php' ? 'active' : '' ?>" onclick="toggleMenu(this)">
        <span class="icon">👥</span> Voter Management
        <span class="arrow">▼</span>
      </button>
      <div class="submenu" style="display: <?= $current === 'voter_management.php' ? 'block' : 'none' ?>;">
        <a href="voter_management.php" class="submenu-link <?= $current === 'voter_management.php' ? 'active' : '' ?>">Manage Voters</a>
      </div>
    </div>

    <!-- Audit & Security -->
    <div class="nav-item">
      <button class="nav-toggle <?= in_array($current, ['view_audit.php', 'tamper_check.php']) ? 'active' : '' ?>" onclick="toggleMenu(this)">
        <span class="icon">🔒</span> Audit & Security
        <span class="arrow">▼</span>
      </button>
      <div class="submenu" style="display: <?= in_array($current, ['view_audit.php', 'tamper_check.php']) ? 'block' : 'none' ?>;">
        <a href="view_audit.php" class="submenu-link <?= $current === 'view_audit.php' ? 'active' : '' ?>">📝 Audit Logs</a>
        <a href="../backend/tamper_check.php" class="submenu-link <?= $current === 'tamper_check.php' ? 'active' : '' ?>">🔍 Tamper Check</a>
      </div>
    </div>

    <!-- Logout -->
    <div class="nav-item" style="margin-top: 20px;">
      <a href="../backend/logout.php" class="nav-link logout-link">
        <span class="icon">🚪</span> Logout
      </a>
    </div>
  </nav>

  <script>
  function toggleMenu(button) {
    const submenu = button.nextElementSibling;
    const allMenus = document.querySelectorAll('.accordion-nav .submenu');
    const allButtons = document.querySelectorAll('.accordion-nav .nav-toggle');

    // Close all other menus
    allMenus.forEach(menu => {
      if (menu !== submenu) {
        menu.style.display = 'none';
      }
    });

    // Remove active class from all buttons
    allButtons.forEach(btn => {
      if (btn !== button) {
        btn.classList.remove('active');
      }
    });

    // Toggle current menu
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
