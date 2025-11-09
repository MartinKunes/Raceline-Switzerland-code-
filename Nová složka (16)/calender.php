<?php
// View mode: 'month' or 'week'
$view = $_GET['view'] ?? 'month';
$refDate = $_GET['date'] ?? date('Y-m-d');

// Initialize date context for month or week view
if ($view === 'month') {
  $month = $_GET['month'] ?? date('m');
  $year = $_GET['year'] ?? date('Y');

  $firstDay = new DateTime("$year-$month-01");
  $daysInMonth = (int)$firstDay->format('t');
  $startWeekDay = (int)$firstDay->format('N');
} else {
  $dt = new DateTime($refDate);
  $startOfWeek = (clone $dt)->modify('monday this week');
  $startOfWeek->setTime(0,0,0);
}

// Data storage
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/trainings.json';
// Ratings storage
$ratingsFile = $dataDir . '/training_ratings.json';

// Handle POST to add a workout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_workout') {
  // basic sanitization
  $date = $_POST['date'] ?? date('Y-m-d');
  $startTime = $_POST['start_time'] ?? '09:00';
  $endTime = $_POST['end_time'] ?? '10:00';
  $title = trim($_POST['title'] ?? 'Workout');
  $coach = trim($_POST['coach'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $type = $_POST['type'] ?? 'Gym';

  $new = [
    'id' => time(),
    'title' => $title,
    'type' => $type,
    'coach' => $coach,
    'location' => $location,
    'start' => $date . ' ' . $startTime,
    'end' => $date . ' ' . $endTime,
    'notes' => $notes,
    'exercises' => []
  ];

  if (!empty($_POST['exercises_json'])) {
    $decoded = json_decode($_POST['exercises_json'], true);
    if (is_array($decoded)) {
      // Ensure AU is computed for Gym exercises (server-authoritative)
      if ($type === 'Gym') {
        foreach ($decoded as &$ex) {
          $sets = isset($ex['sets']) ? (float)$ex['sets'] : 0;
          $reps = isset($ex['reps']) ? (float)$ex['reps'] : 0;
          $pct1rm = isset($ex['pct1rm']) ? (float)$ex['pct1rm'] : (isset($ex['pct']) ? (float)$ex['pct'] : 0);
          $mult = isset($ex['multiplier']) ? (float)$ex['multiplier'] : (isset($ex['mult']) ? (float)$ex['mult'] : 1);
          $au = round(($sets * $reps * ($pct1rm/100) * $mult), 2);
          $ex['pct1rm'] = $pct1rm;
          $ex['multiplier'] = $mult;
          $ex['au'] = $au;
        }
        unset($ex);
      }
      // Compute AU for Track exercises
      if ($type === 'Track') {
        foreach ($decoded as &$ex) {
          $distance = isset($ex['distance']) ? (float)$ex['distance'] : 0;
          $effort = isset($ex['effort']) ? (float)$ex['effort'] : (isset($ex['effort_factor']) ? (float)$ex['effort_factor'] : 1);
          $session = isset($ex['session_factor']) ? (float)$ex['session_factor'] : 1;
          $restRatio = isset($ex['rest_ratio']) ? (float)$ex['rest_ratio'] : (isset($ex['restRatio']) ? (float)$ex['restRatio'] : 1);
          $sets = isset($ex['sets']) ? (float)$ex['sets'] : (isset($ex['s']) ? (float)$ex['s'] : 1);
          $reps = isset($ex['reps']) ? (float)$ex['reps'] : (isset($ex['r']) ? (float)$ex['r'] : 1);
          // AU = distance x (effort x session) x rest ratio x sets x reps / 6
          $au = round(($distance * ($effort * $session) * $restRatio * $sets * $reps / 6), 2);
          $ex['distance'] = $distance;
          $ex['effort'] = $effort;
          $ex['session_factor'] = $session;
          $ex['rest_ratio'] = $restRatio;
          $ex['au'] = $au;
        }
        unset($ex);
      }

      // Compute AU for Sprint exercises
      if ($type === 'Sprint') {
        foreach ($decoded as &$ex) {
          $distance = isset($ex['distance']) ? (float)$ex['distance'] : 0;
          $effort = isset($ex['effort']) ? (float)$ex['effort'] : (isset($ex['effort_factor']) ? (float)$ex['effort_factor'] : 1);
          $session = isset($ex['session_factor']) ? (float)$ex['session_factor'] : 1;
          $recovery = isset($ex['recovery_ratio']) ? (float)$ex['recovery_ratio'] : (isset($ex['recovery']) ? (float)$ex['recovery'] : 1);
          $gear = isset($ex['gear_ratio']) ? (float)$ex['gear_ratio'] : (isset($ex['gear']) ? (float)$ex['gear'] : 1);
          $sets = isset($ex['sets']) ? (float)$ex['sets'] : (isset($ex['s']) ? (float)$ex['s'] : 1);
          $reps = isset($ex['reps']) ? (float)$ex['reps'] : (isset($ex['r']) ? (float)$ex['r'] : 1);
          // AU = distance x (effort x session) x recovery ratio x sets x reps x gear ratio / 4
          $au = round(($distance * ($effort * $session) * $recovery * $sets * $reps * $gear / 4), 2);
          $ex['distance'] = $distance;
          $ex['effort'] = $effort;
          $ex['session_factor'] = $session;
          $ex['recovery_ratio'] = $recovery;
          $ex['gear_ratio'] = $gear;
          $ex['au'] = $au;
        }
        unset($ex);
      }
      $new['exercises'] = $decoded;
    }
  }

  if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
  $existing = [];
  if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $existing = json_decode($json, true) ?: [];
  }
  $existing[] = $new;
  $writeResult = file_put_contents($dataFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  // Debug: write last POST payload and what was saved (helpful when debugging persistence)
  $debug = [
    'posted' => $_POST,
    'new' => $new,
    'write_result' => $writeResult,
    'dataFile' => $dataFile,
    'timestamp' => date(DATE_ATOM)
  ];
  @file_put_contents($dataDir . '/last_post.json', json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  // Redirect to avoid form resubmission
  $qs = $_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : '';
  header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
  exit;
}

// Handle POST to delete a workout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_training') {
  $delId = $_POST['delete_id'] ?? null;
  if ($delId !== null) {
    if (file_exists($dataFile)) {
      $json = file_get_contents($dataFile);
      $existing = json_decode($json, true) ?: [];
      $filtered = array_values(array_filter($existing, function($t) use ($delId){
        return (string)($t['id'] ?? '') !== (string)$delId;
      }));
      file_put_contents($dataFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
  }
  // redirect back
  $qs = $_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : '';
  header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
  exit;
}

// Handle POST to rate a training (prevent overwrite)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rate_training') {
  $tid = $_POST['training_id'] ?? null;
  $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
  $result = ['ok' => false];
  if ($tid && $rating && $rating >= 1 && $rating <= 10) {
    if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
    $existing = [];
    if (file_exists($ratingsFile)) {
      $json = file_get_contents($ratingsFile);
      $existing = json_decode($json, true) ?: [];
    }
    // check if this training already has a rating
    foreach ($existing as $r) if ((string)$r['training_id'] === (string)$tid) { $result['error'] = 'already_rated';
    }
    if (empty($result['error'])) {
      $entry = ['training_id' => (string)$tid, 'rating' => $rating, 'created' => date('Y-m-d H:i')];
      $existing[] = $entry;
      file_put_contents($ratingsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      $result = ['ok' => true, 'rating' => $rating, 'created' => $entry['created']];
    }
  } else {
    $result['error'] = 'invalid';
  }

  // If client expects JSON, return JSON and stop
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  if (strpos($accept, 'application/json') !== false) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }

  // Otherwise redirect back
  $qs = $_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : '';
  header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
  exit;
}

// Default example trainings (kept minimal to avoid overwriting user data)
$defaultTrainings = [];

$userTrainings = [];
if (file_exists($dataFile)) {
  $json = file_get_contents($dataFile);
  $userTrainings = json_decode($json, true) ?: [];
}

// Load existing ratings
$ratings = [];
if (file_exists($ratingsFile)) {
  $rjson = file_get_contents($ratingsFile);
  $ratings = json_decode($rjson, true) ?: [];
}
// map by training_id for quick lookup
$ratingsMap = [];
foreach ($ratings as $r) {
  if (isset($r['training_id'])) $ratingsMap[(string)$r['training_id']] = $r;
}

// Merge defaults and user-created trainings (user trainings last so they show as added)
$trainings = array_merge($defaultTrainings, $userTrainings);

$trainingByDay = [];
foreach ($trainings as $t) {
  // attach rating if present
  $tid = (string)($t['id'] ?? '');
  if ($tid && isset($ratingsMap[$tid])) {
    $t['rating'] = $ratingsMap[$tid]['rating'];
    $t['rating_created'] = $ratingsMap[$tid]['created'] ?? null;
  }
  $dateKey = date('Y-m-d', strtotime($t['start']));
  $trainingByDay[$dateKey][] = $t;
}

// Compute total AU for the selected period (month or week)
$periodTotalAU = 0.0;
foreach ($trainings as $t) {
  $tStart = strtotime($t['start'] ?? '');
  if (!$tStart) continue;
  $include = false;
  if ($view === 'month') {
    $y = (int)date('Y', $tStart);
    $m = (int)date('m', $tStart);
    if ($y === (int)$year && $m === (int)$month) $include = true;
  } else {
    // week view: include trainings from startOfWeek .. +6 days
    $start = clone $startOfWeek;
    $end = (clone $startOfWeek)->modify('+6 days');
    $ts = (int)$tStart;
    if ($ts >= $start->getTimestamp() && $ts <= $end->getTimestamp()) $include = true;
  }
  if ($include) {
    $tAu = 0.0;
    if (isset($t['exercises']) && is_array($t['exercises'])) {
      foreach ($t['exercises'] as $ex) {
        if (isset($ex['au'])) $tAu += (float)$ex['au'];
      }
    }
    $periodTotalAU += $tAu;
  }
}
// format for display
$periodTotalAU = round($periodTotalAU, 2);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kalendář tréninků</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
  .calendar th, .calendar td {
    width: 14.28%;
    height: 140px;
    vertical-align: top;
    border: 1px solid #dee2e6;
    padding: 6px;
  }
  .calendar .day-number { font-weight: 600; margin-bottom: 4px; font-size: 0.9rem; }
  .training {
    border-radius: 8px;
    font-size: 0.9rem;
    padding: 4px 6px;
    margin-top: 6px;
    color: #fff;
    cursor: pointer;
    line-height: 1.2;
  }
  .type-Gym { background-color: #0d6efd; }
  .type-Sprint { background-color: #198754; }
  .type-Track { background-color: #dc3545; }
  .type-Recovery { background-color: #6c757d; }
  .weekend { background-color: #f8f9fa; }
  .custom-tooltip {
    max-width: 250px;
    font-size: 0.9rem;
    padding: 8px 10px;
    background-color: rgba(0,0,0,0.85);
    border-radius: 8px;
  }
  /* Responsive tweaks */
  @media (max-width: 991.98px) { /* tablets and below */
    .calendar th, .calendar td { height: 120px; padding: 4px; }
    .calendar .day-number { font-size: 0.85rem; }
    .training { font-size: 0.85rem; padding: 3px 5px; }
  }
  @media (max-width: 575.98px) { /* mobile */
    .calendar th, .calendar td { height: 100px; padding: 3px; }
    .calendar .day-number { font-size: 0.8rem; }
    .training { font-size: 0.75rem; padding: 2px 4px; }
    .calendar { display: block; overflow-x: auto; white-space: nowrap; }
    .calendar table { min-width: 700px; }
  }
</style>
</head>
<body class="bg-light">

<?php
// Include shared subheader/menu
$subheader = __DIR__ . '/subheader.php';
if (file_exists($subheader)) include_once $subheader;
?>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <!-- View toggle -->
      <a href="?view=month&month=<?= $month ?? date('m') ?>&year=<?= $year ?? date('Y') ?>" class="btn btn-sm btn-outline-secondary<?= $view === 'month' ? ' active' : '' ?>">Měsíc</a>
      <a href="?view=week&date=<?= ($view === 'week' ? htmlspecialchars($refDate) : date('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary<?= $view === 'week' ? ' active' : '' ?>">Týden</a>
    </div>
    <div class="d-flex align-items-center">
      <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">Přidat workout</button>
      <?php if ($view === 'month'): ?>
        <a href="?view=month&month=<?= $month == 1 ? 12 : $month - 1 ?>&year=<?= $month == 1 ? $year - 1 : $year ?>" class="btn btn-outline-primary btn-sm">&laquo; Předchozí</a>
  <span class="mx-3 h5 mb-0"><?= strftime('%B %Y', $firstDay->getTimestamp()) ?> <span class="badge bg-info text-dark ms-2">AU: <?= htmlspecialchars($periodTotalAU) ?></span></span>
        <a href="?view=month&month=<?= $month == 12 ? 1 : $month + 1 ?>&year=<?= $month == 12 ? $year + 1 : $year ?>" class="btn btn-outline-primary btn-sm">Další &raquo;</a>
      <?php else: ?>
        <?php
          $prevWeek = (clone $startOfWeek)->modify('-7 days');
          $nextWeek = (clone $startOfWeek)->modify('+7 days');
        ?>
        <a href="?view=week&date=<?= $prevWeek->format('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">&laquo; Předchozí</a>
  <span class="mx-3 h5 mb-0"><?= $startOfWeek->format('j. M Y') ?> - <?= $startOfWeek->modify('+6 days')->format('j. M Y') ?> <span class="badge bg-info text-dark ms-2">AU: <?= htmlspecialchars($periodTotalAU) ?></span></span>
        <?php $startOfWeek->modify('-6 days'); // restore ?>
        <a href="?view=week&date=<?= $nextWeek->format('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">Další &raquo;</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table calendar text-center">
    <thead class="table-secondary">
      <tr>
        <th>Po</th><th>Út</th><th>St</th><th>Čt</th><th>Pá</th><th>So</th><th>Ne</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($view === 'month') {
        $day = 1;
        echo "<tr>";
        for ($i = 1; $i < $startWeekDay; $i++) echo "<td></td>";

    for ($i = $startWeekDay; $i <= 7; $i++) {
      $isWeekend = ($i >= 6) ? 'weekend' : '';
      $curDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
      echo "<td class='$isWeekend'><div class='day-number'>$day</div>";
      if (isset($trainingByDay[$curDate])) {
        foreach ($trainingByDay[$curDate] as $tr) {
          $tooltipContent = "<strong>{$tr['title']}</strong><br>{$tr['location']}<br>".date('H:i', strtotime($tr['start']))." - ".date('H:i', strtotime($tr['end']));
          echo "<div class='training type-{$tr['type']}' data-bs-toggle='tooltip' data-bs-html='true' title=\"$tooltipContent\" data-training='" . htmlspecialchars(json_encode($tr), ENT_QUOTES) . "' onclick='showTrainingModal(this)'>{$tr['title']}<br><small>" . date('H:i', strtotime($tr['start'])) . "</small></div>";
        }
      }
      echo "</td>";
      $day++;
    }
        echo "</tr>";

        while ($day <= $daysInMonth) {
            echo "<tr>";
            for ($i = 1; $i <= 7; $i++) {
                if ($day <= $daysInMonth) {
                    $isWeekend = ($i >= 6) ? 'weekend' : '';
                    echo "<td class='$isWeekend'><div class='day-number'>$day</div>";
          $curDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
          if (isset($trainingByDay[$curDate])) {
            foreach ($trainingByDay[$curDate] as $tr) {
              $tooltipContent = "<strong>{$tr['title']}</strong><br>{$tr['location']}<br>".date('H:i', strtotime($tr['start']))." - ".date('H:i', strtotime($tr['end']));
              echo "<div class='training type-{$tr['type']}' data-bs-toggle='tooltip' data-bs-html='true' title=\"$tooltipContent\" data-training='" . htmlspecialchars(json_encode($tr), ENT_QUOTES) . "' onclick='showTrainingModal(this)'>{$tr['title']}<br><small>" . date('H:i', strtotime($tr['start'])) . "</small></div>";
            }
          }
                    echo "</td>";
                } else echo "<td></td>";
                $day++;
            }
            echo "</tr>";
        }
      } else {
        // Week view: render single row for the week
        echo "<tr>";
        for ($d = 0; $d < 7; $d++) {
            $cur = (clone $startOfWeek)->modify("+$d days");
            $label = $cur->format('j.');
            $isWeekend = ($cur->format('N') >= 6) ? 'weekend' : '';
            echo "<td class='$isWeekend'><div class='day-number'>$label</div>";

            // list trainings falling on this date
            foreach ($trainings as $tr) {
                $trDate = date('Y-m-d', strtotime($tr['start']));
                if ($trDate === $cur->format('Y-m-d')) {
                    $tooltipContent = "<strong>{$tr['title']}</strong><br>{$tr['location']}<br>".date('H:i', strtotime($tr['start']))." - ".date('H:i', strtotime($tr['end']));
                    echo "<div class='training type-{$tr['type']}' data-bs-toggle='tooltip' data-bs-html='true' title=\"$tooltipContent\" data-training='" . htmlspecialchars(json_encode($tr), ENT_QUOTES) . "' onclick='showTrainingModal(this)'>{$tr['title']}<br><small>" . date('H:i', strtotime($tr['start'])) . "</small></div>";
                }
            }

            echo "</td>";
        }
        echo "</tr>";
      }
      ?>
    </tbody>
    </table>
  </div>

  <?php
  // Include extracted Add Workout modal fragment (keeps calendar file tidy)
  $modalFile = __DIR__ . '/add_workout_modal.php';
  if (file_exists($modalFile)) include $modalFile;
  ?>

<!-- MODAL -->
<div class="modal fade" id="trainingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Detail tréninku</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
  <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <p><strong>Název:</strong> <span id="modalTitle"></span></p>
            <p><strong>Typ:</strong> <span id="modalType"></span></p>
            <p><strong>Trenér:</strong> <span id="modalCoach"></span></p>
          </div>
          <div class="col-md-6">
            <p><strong>Místo:</strong> <span id="modalLocation"></span></p>
            <p><strong>Čas:</strong> <span id="modalTime"></span></p>
          </div>
        </div>
        <hr>
        <h6 class="mt-3 mb-2">Plán</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle text-center">
            <thead class="table-light" id="exerciseTableHead"></thead>
            <tbody id="exerciseTableBody"></tbody>
          </table>
        </div>
        <hr>
        <p><strong>Poznámky:</strong><br><span id="modalNotes"></span></p>
        <hr>
        <div id="ratingSection" class="mt-3">
          <h6>Hodnocení náročnosti</h6>
          <div id="ratingContainer"></div>
        </div>
      </div>
      <div class="modal-footer">
        <form method="post" onsubmit="return confirm('Opravdu odstranit tento trénink?');" class="me-auto" id="deleteTrainingForm">
          <input type="hidden" name="action" value="delete_training">
          <input type="hidden" name="delete_id" id="delete_id" value="">
          <button type="submit" class="btn btn-sm btn-danger" id="deleteBtn">Smazat trénink</button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(el => new bootstrap.Tooltip(el, { customClass: 'custom-tooltip', placement: 'top' }))
});

function showTrainingModal(el) {
  let data = null;
  try {
    data = JSON.parse(el.dataset.training);
  } catch (err) {
    // fallback: decode HTML entities then parse
    try {
      const ta = document.createElement('textarea');
      ta.innerHTML = el.getAttribute('data-training');
      data = JSON.parse(ta.value);
    } catch (err2) {
      console.error('Failed to parse training data', err, err2);
      data = {};
    }
  }
  // keep reference to the source element so we can update its dataset after rating
  const sourceEl = el;
  document.getElementById('modalTitle').textContent = data.title;
  document.getElementById('modalType').textContent = data.type;
  document.getElementById('modalCoach').textContent = data.coach;
  document.getElementById('modalLocation').textContent = data.location;
  document.getElementById('modalTime').textContent =
    new Date(data.start).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) +
    " - " +
    new Date(data.end).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  document.getElementById('modalNotes').textContent = data.notes;

  // Set delete id for the footer form
  const delInput = document.getElementById('delete_id');
  if (delInput) delInput.value = data.id || '';

  // Render rating UI
  const ratingContainer = document.getElementById('ratingContainer');
  if (ratingContainer) {
    ratingContainer.innerHTML = '';
    if (data.rating) {
      ratingContainer.innerHTML = `<div class="badge bg-primary">Hodnocení: ${data.rating}</div> <small class="text-muted">(${data.rating_created||''})</small>`;
    } else {
      ratingContainer.innerHTML = `
        <div class="d-flex align-items-center gap-2">
          <input type="range" id="ratingSlider" min="1" max="10" value="5" class="form-range">
          <span id="ratingValue" class="badge bg-secondary">5</span>
          <button id="submitRating" class="btn btn-sm btn-success">Odeslat hodnocení</button>
        </div>
      `;
      const slider = document.getElementById('ratingSlider');
      const rv = document.getElementById('ratingValue');
      const btn = document.getElementById('submitRating');
      if (slider && rv) slider.addEventListener('input', ()=> rv.textContent = slider.value);
      if (btn) btn.addEventListener('click', function(){
        btn.disabled = true; rv.textContent = '...';
        fetch(window.location.pathname, {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
          body: new URLSearchParams({action:'rate_training', training_id: data.id, rating: slider.value})
        }).then(r=>r.json()).then(js=>{
          if (js.ok) {
            const created = js.created || '';
            // update the in-memory data and the source element so reopening the modal shows the rating
            try {
              data.rating = js.rating;
              data.rating_created = created;
              if (sourceEl && sourceEl.dataset) sourceEl.dataset.training = JSON.stringify(data);
            } catch (e) { console.error('Failed updating source element dataset', e); }
            ratingContainer.innerHTML = `<div class="badge bg-primary">Hodnocení: ${js.rating}</div> <small class="text-muted">(${created})</small>`;
          } else if (js.error === 'already_rated') {
            ratingContainer.innerHTML = `<div class="text-danger">Tento trénink již byl hodnocen.</div>`;
          } else {
            ratingContainer.innerHTML = `<div class="text-danger">Chyba při ukládání.</div>`;
          }
        }).catch(err=>{ ratingContainer.innerHTML = `<div class="text-danger">Chyba sítě</div>`; console.error(err); });
      });
    }
  }

  const tbody = document.getElementById('exerciseTableBody');
  const thead = document.getElementById('exerciseTableHead');
  tbody.innerHTML = '';
  thead.innerHTML = '';

  if (!data.exercises || data.exercises.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Žádné záznamy</td></tr>';
  } else {
    let headers = [];
    let rows = '';

    switch (data.type) {
      case 'Gym':
        headers = ['Cvik', 'Série', 'Opakování', '%1RM', 'Multiplier', 'AU'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.sets}</td><td>${ex.reps}</td><td>${ex.pct1rm ?? (ex.pct||'')}</td><td>${ex.multiplier ?? ''}</td><td><span class="badge bg-success">${ex.au ?? ''}</span></td></tr>
        `).join('');
        break;

      case 'Sprint':
        headers = ['Cvik','Vzdálenost (m)','Effort','Session factor','Recovery ratio','Gear ratio','Série','Opakování','AU'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.distance ?? ''}</td><td>${ex.effort ?? ex.effort_factor ?? ''}</td><td>${ex.session_factor ?? ''}</td><td>${ex.recovery_ratio ?? ''}</td><td>${ex.gear_ratio ?? ''}</td><td>${ex.sets ?? ''}</td><td>${ex.reps ?? ''}</td><td><span class="badge bg-success">${ex.au ?? ''}</span></td></tr>
        `).join('');
        break;

      case 'Track':
        headers = ['Cvik', 'Vzdálenost (m)', 'Effort', 'Session factor', 'Rest ratio', 'Série', 'Opakování', 'AU'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.distance}</td><td>${ex.effort ?? ex.effort_factor ?? ''}</td><td>${ex.session_factor ?? ''}</td><td>${ex.rest_ratio ?? ''}</td><td>${ex.sets ?? ''}</td><td>${ex.reps ?? ''}</td><td><span class="badge bg-success">${ex.au ?? ''}</span></td></tr>
        `).join('');
        break;

      case 'Recovery':
        headers = ['Aktivita', 'Délka (min)', 'Pomůcky', 'Poznámka'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.duration}</td><td>${ex.tools}</td><td>${ex.notes}</td></tr>
        `).join('');
        break;

      default:
        headers = Object.keys(data.exercises[0]);
        rows = data.exercises.map(ex => `<tr>${headers.map(h => `<td>${ex[h] ?? '-'}</td>`).join('')}</tr>`).join('');
    }

    thead.innerHTML = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
    tbody.innerHTML = rows;
  }

  new bootstrap.Modal(document.getElementById('trainingModal')).show();
}
</script>

<!-- exercise builder script moved into add_workout_modal.php -->

</body>

</html>