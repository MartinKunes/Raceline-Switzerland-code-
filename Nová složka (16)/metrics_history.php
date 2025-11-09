<?php
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/metrics.json';

// Handle POST add_metric
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_metric') {
  $entry = [
    'id' => time(),
    'date' => $_POST['date'] ?? date('Y-m-d'),
    'sleep' => is_numeric($_POST['sleep'] ?? null) ? (float)$_POST['sleep'] : null,
    'mood' => is_numeric($_POST['mood'] ?? null) ? (int)$_POST['mood'] : null,
    'soreness' => is_numeric($_POST['soreness'] ?? null) ? (int)$_POST['soreness'] : null,
    'resting_hr' => is_numeric($_POST['resting_hr'] ?? null) ? (int)$_POST['resting_hr'] : null,
    'hrv' => is_numeric($_POST['hrv'] ?? null) ? (int)$_POST['hrv'] : null,
    'fatigue' => is_numeric($_POST['fatigue'] ?? null) ? (int)$_POST['fatigue'] : null,
    // store created as "Y-m-d H:i"
    'created' => date('Y-m-d H:i')
  ];

  if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
  $existing = [];
  if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $existing = json_decode($json, true) ?: [];
  }
  $existing[] = $entry;
  file_put_contents($dataFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  // redirect back to avoid form resubmission
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

$entries = [];
if (file_exists($dataFile)) {
  $json = file_get_contents($dataFile);
  $entries = json_decode($json, true) ?: [];
}
// sort by date desc
usort($entries, function($a,$b){ return strcmp($b['date'],$a['date']); });

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_metric') {
  $delId = $_POST['delete_id'] ?? null;
  if ($delId !== null && file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $existing = json_decode($json, true) ?: [];
    $filtered = array_values(array_filter($existing, function($e) use ($delId){ return (string)$e['id'] !== (string)$delId; }));
    file_put_contents($dataFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historie metrik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include __DIR__ . '/subheader.php'; ?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Historie metrik</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#metricsModal">Přidat záznam</button>
  </div>

  <div class="row">
    <?php if (empty($entries)): ?>
      <div class="col-12"><div class="alert alert-secondary">Žádné záznamy</div></div>
    <?php else: foreach ($entries as $e): ?>
      <div class="col-12 mb-2">
        <div class="card shadow-sm">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold"><?= htmlspecialchars($e['date']) ?> <small class="text-muted">(<?= htmlspecialchars($e['created'] ?? '-') ?>)</small></div>
              <div class="mt-2">
                <span class="me-2"><strong>Sleep:</strong> <span class="badge bg-info text-dark"><?= $e['sleep'] ?? '-' ?></span></span>
                <span class="me-2"><strong>Mood:</strong> <span class="badge bg-primary"><?= $e['mood'] ?? '-' ?></span></span>
                <span class="me-2"><strong>Soreness:</strong> <span class="badge bg-warning text-dark"><?= $e['soreness'] ?? '-' ?></span></span>
                <span class="me-2"><strong>Resting HR:</strong> <span class="badge bg-secondary"><?= $e['resting_hr'] ?? '-' ?></span></span>
                <span class="me-2"><strong>HRV:</strong> <span class="badge bg-success"><?= $e['hrv'] ?? '-' ?></span></span>
                <span class="me-2"><strong>Fatigue:</strong> <span class="badge bg-danger"><?= $e['fatigue'] ?? '-' ?></span></span>
              </div>
            </div>
            <div class="text-end">
              <form method="post" onsubmit="return confirm('Opravdu smazat tento záznam?');">
                <input type="hidden" name="action" value="delete_metric">
                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($e['id']) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Smazat</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <p class="text-muted small">Záznamy se ukládají do <code>data/metrics.json</code>.</p>
</div>
</body>
</html>
