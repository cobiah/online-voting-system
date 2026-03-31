<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
if (isset($_SESSION['admin']) && !isset($_SESSION['student_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

include '../backend/db.php';

$currentElection = db_get_current_election();
$electionOpen = $currentElection !== null;
$electionMessage = $electionOpen ? 'Voting is currently open by admin.' : 'Voting is currently closed.';

$studentId = (int) $_SESSION['student_id'];
$student = db_get_student_by_id($studentId) ?? [];
$studentDeptId = (int) ($student['department_id'] ?? 0);
$studentDepartment = (string) ($student['department'] ?? 'Unknown Department');
if ($studentDepartment === '') {
    $studentDepartment = 'Unknown Department';
}

$positions = [];
foreach (db_get_positions() as $row) {
    $positions[$row['position_id']] = $row['position_name'];
}

$positionIds = array_keys($positions);
$voteCount = db_count_votes_for_student($studentId);
$votedPositionIds = [];
foreach (db_find_many('votes', ['student_id' => $studentId]) as $row) {
    $votedPositionIds[] = (int) $row['position_id'];
}

$remainingPositionIds = array_values(array_diff($positionIds, $votedPositionIds));
$hasVoted = $voteCount > 0 || empty($remainingPositionIds);

$candidatesByPositionId = [];
foreach (db_get_candidates_for_department($studentDeptId, $studentDepartment) as $row) {
    $candidatesByPositionId[$row['position_id']][] = $row;
}
?>
<?php include '../includes/header.php'; ?>

<div class="dashboard">
  <?php include '../includes/sidebar_student.php'; ?>

  <section class="panel">
    <h2>Cast Your Vote</h2>
    <p>Select a candidate below and submit your vote.</p>

    <?php if (!$electionOpen): ?>
      <div class="alert error">
        <span class="icon">âš ï¸</span>
        <span><?= htmlspecialchars($electionMessage) ?></span>
      </div>
      <div style="margin-top: 16px;">
        <a href="dashboard.php" class="button button-secondary">Back to Dashboard</a>
      </div>
    <?php elseif ($hasVoted): ?>
      <div class="alert success">
        <span class="icon">âœ…</span>
        <span>You have already cast your vote. You can now only view the results.</span>
      </div>
      <div style="margin-top: 16px; display: grid; gap: 12px;">
        <a href="results.php" class="button button-primary">View Results</a>
        <a href="dashboard.php" class="button button-secondary">Back to Dashboard</a>
      </div>
    <?php elseif (empty($candidatesByPositionId)): ?>
      <div class="alert error">
        <span class="icon">âš ï¸</span>
        <span>No candidates are currently available. Please check back later.</span>
      </div>
    <?php else: ?>
      <div class="alert info" style="margin-bottom: 18px;">
        <span class="icon">ðŸ“</span>
        <span>Department ballot: <?= htmlspecialchars($studentDepartment) ?></span>
      </div>

      <form id="voteForm" action="<?= htmlspecialchars(app_url('backend/vote.php')) ?>" method="post" onsubmit="return confirmVote();">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
        <div class="card" style="margin-bottom: 18px;">
          <button type="button" class="button button-secondary" style="width:100%; text-align:left; margin-bottom: 12px;" onclick="toggleDepartmentAccordion()">
            <?= htmlspecialchars($studentDepartment) ?> ballot â€“ expand / collapse
          </button>
          <div id="departmentAccordion">
            <?php foreach ($remainingPositionIds as $positionId): ?>
          <div class="card department-ballot" style="margin-bottom: 18px;">
            <h3 style="margin-top: 0;"><?= htmlspecialchars($positions[$positionId]) ?></h3>

            <?php if (empty($candidatesByPositionId[$positionId] ?? [])): ?>
              <div class="alert error">
                <span class="icon">âš ï¸</span>
                <span>No candidates are available for this position in your department.</span>
              </div>
            <?php else: ?>
              <?php foreach ($candidatesByPositionId[$positionId] as $candidate):
                $photoStyle = '';
                if (!empty($candidate['image_url'])) {
                    $photoStyle = "background-image: url('" . htmlspecialchars($candidate['image_url'], ENT_QUOTES) . "');";
                }
            ?>
                <label class="candidate-card">
                  <input type="radio" data-candidate-name="<?= htmlspecialchars($candidate['name']) ?>" name="candidate_id[<?= $positionId ?>]" value="<?= $candidate['candidate_id'] ?>" required>
                  <div class="candidate-photo" style="<?= $photoStyle ?>" <?php if (!empty($candidate['image_url'])): ?> onclick="showImageModal('<?= htmlspecialchars($candidate['image_url'], ENT_QUOTES) ?>')" <?php endif; ?> >
                    <?php if (empty($candidate['image_url'])): ?>
                      <?= strtoupper(substr(htmlspecialchars($candidate['name']), 0, 1)) ?>
                    <?php endif; ?>
                  </div>
                  <div class="candidate-details">
                    <div class="candidate-title"><?= htmlspecialchars($candidate['name']) ?></div>
                    <div class="candidate-meta"><?= htmlspecialchars($candidate['manifesto'] ?: 'No manifesto provided.') ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
          </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
          <button class="button button-secondary" type="button" onclick="renderBallotPreview()">Preview Ballot</button>
          <button class="button button-primary" type="submit">Submit Vote</button>
        </div>

        <div id="ballotPreview" class="card" style="display:none; margin-top: 20px;">
          <h3>Ballot Preview</h3>
          <div id="previewContent" style="margin-bottom: 16px;"></div>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="button button-secondary" type="button" onclick="hideBallotPreview()">Edit Ballot</button>
            <button class="button button-primary" type="submit">Confirm and Submit</button>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>

<script>
function renderBallotPreview() {
  const voteForm = document.getElementById('voteForm');
  const rows = voteForm.querySelectorAll('.department-ballot');
  const previewContent = document.getElementById('previewContent');
  const ballotEntries = [];
  let hasMissing = false;

  rows.forEach(row => {
    const positionName = row.querySelector('h3').textContent.trim();
    const selected = row.querySelector('input[type="radio"]:checked');
    if (selected) {
      ballotEntries.push({ position: positionName, candidate: selected.dataset.candidateName || 'Unknown candidate' });
    } else {
      ballotEntries.push({ position: positionName, candidate: null });
      hasMissing = true;
    }
  });

  if (hasMissing) {
    alert('Please select a candidate for every position before previewing your ballot.');
    return;
  }

  if (ballotEntries.length === 0) {
    alert('No ballot selections are available yet.');
    return;
  }

  previewContent.innerHTML = ballotEntries.map(entry => {
    return '<div class=\"preview-row\"><strong>' + entry.position + '</strong>: ' + entry.candidate + '</div>';
  }).join('');

  document.getElementById('ballotPreview').style.display = 'block';
}

function hideBallotPreview() {
  document.getElementById('ballotPreview').style.display = 'none';
}

function toggleDepartmentAccordion() {
  const section = document.getElementById('departmentAccordion');
  if (!section) {
    return;
  }
  section.style.display = section.style.display === 'none' ? 'block' : 'none';
}

function confirmVote() {
  return confirm('This action is final. Are you sure you want to submit your ballot?');
}

function showImageModal(src) {
  const modal = document.getElementById('imageModal');
  const img = document.getElementById('imageModalSrc');
  img.src = src;
  modal.classList.add('show');
}

function closeImageModal() {
  const modal = document.getElementById('imageModal');
  const img = document.getElementById('imageModalSrc');
  modal.classList.remove('show');
  img.src = '';
}
</script>

<div id="imageModal" class="image-modal" onclick="closeImageModal()">
  <div class="image-modal-content" onclick="event.stopPropagation();">
    <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
    <img id="imageModalSrc" src="" alt="Candidate photo">
  </div>
</div>

<?php include '../includes/footer.php'; ?>
