<?php
include '../backend/db.php';
include '../includes/header.php';

$results = [];
$positionTotals = [];
$isPresentation = presentation_mode_enabled() && !database_ready($conn);

if ($isPresentation) {
    $results = presentation_public_results();
    foreach ($results as $position => $candidates) {
        foreach ($candidates as $row) {
            $positionTotals[$position] = ($positionTotals[$position] ?? 0) + (int) $row['total_votes'];
        }
    }
} else {
    foreach (db_get_public_results() as $row) {
        $position = $row['position_name'];
        $results[$position][] = $row;
        $positionTotals[$position] = ($positionTotals[$position] ?? 0) + (int)$row['total_votes'];
    }
}
?>

<div class="dashboard">
  <?php include '../includes/sidebar_student.php'; ?>

  <section class="panel">
    <h2>Election Results</h2>
    <p>Current vote totals by candidate.</p>

    <?php if ($isPresentation): ?>
      <div class="alert info">
        <span class="icon">i</span>
        <span><?= htmlspecialchars(presentation_notice()) ?></span>
      </div>
    <?php endif; ?>

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
              <div style="display:flex; align-items:center; gap:14px;">
                <div class="result-avatar" style="<?= !empty($row['image_url']) ? 'background-image: url(\'' . htmlspecialchars($row['image_url'], ENT_QUOTES) . '\');' : '' ?>" <?php if (!empty($row['image_url'])): ?> onclick="showImageModal('<?= htmlspecialchars($row['image_url'], ENT_QUOTES) ?>')" <?php endif; ?> >
                  <?php if (empty($row['image_url'])): ?>
                    <?= strtoupper(substr(htmlspecialchars($row['name']), 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div class="result-info">
                  <div class="result-name"><?= htmlspecialchars($row['name']) ?></div>
                  <div class="result-meta"><?= $total ?> votes &middot; <?= $pct ?>%</div>
                </div>
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

  <div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <div class="image-modal-content" onclick="event.stopPropagation();">
      <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
      <img id="imageModalSrc" src="" alt="Candidate photo">
    </div>
  </div>

  <script>
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

<?php include '../includes/footer.php'; ?>
