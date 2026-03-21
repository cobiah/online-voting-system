<?php
include '../backend/db.php';
include '../includes/header.php';

$results = [];
$positionTotals = [];

$sql = "SELECT c.candidate_id, c.name, c.position, COUNT(v.vote_id) AS total_votes
        FROM candidates c
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id
        GROUP BY c.position, c.candidate_id, c.name
        ORDER BY c.position, total_votes DESC";

$res = $conn->query($sql);
if (!$res) {
    die('Database error: ' . $conn->error);
}
while ($row = $res->fetch_assoc()) {
    $position = $row['position'];
    $results[$position][] = $row;
    $positionTotals[$position] = ($positionTotals[$position] ?? 0) + (int)$row['total_votes'];
}
?>

<div class="dashboard">
  <?php include '../includes/sidebar_student.php'; ?>

  <section class="panel">
    <h2>Election Results</h2>
    <p>Current vote totals by candidate.</p>

    <?php if (empty($results)): ?>
      <div class="alert error">
        <span class="icon">⚠️</span>
        <span>No results available yet.</span>
      </div>
    <?php else: ?>
      <?php foreach ($results as $position => $candidates): ?>
        <div class="card" style="margin-bottom: 18px;">
          <h3 style="margin-top: 0;"><?= htmlspecialchars($position) ?></h3>
          <div class="results-grid">
            <?php foreach ($candidates as $row):
              $total = (int)$row['total_votes'];
              $denominator = max(1, $positionTotals[$position] ?? 0);
              $pct = $denominator ? round($total / $denominator * 100) : 0;
            ?>
              <div class="result-row">
                <div class="result-info">
                  <div class="result-name"><?= htmlspecialchars($row['name']) ?></div>
                  <div class="result-meta"><?= $total ?> votes &middot; <?= $pct ?>%</div>
                </div>
                <div class="result-bar">
                  <div class="result-bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</div>

<?php include '../includes/footer.php'; ?>