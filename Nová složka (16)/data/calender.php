<?php
// View mode: 'month' or 'week'
$view = $_GET['view'] ?? 'month';

// Reference date (for week view) or month/year for month view
$refDate = $_GET['date'] ?? date('Y-m-d');

if ($view === 'month') {
  $month = $_GET['month'] ?? date('m');
  $year = $_GET['year'] ?? date('Y');

  $firstDay = new DateTime("$year-$month-01");
  $daysInMonth = (int)$firstDay->format('t');
  $startWeekDay = (int)$firstDay->format('N');
} else {
  // Week view: compute start (Monday) and days
  $dt = new DateTime($refDate);
  // Clone because modify mutates
  $startOfWeek = (clone $dt)->modify('monday this week');
  // Ensure time set to midnight
  $startOfWeek->setTime(0,0,0);
}

// Ukázková data
// More test data across the month and surrounding weeks
// default test trainings
$defaultTrainings = [
  ['id'=>1,'title'=>'Silový trénink A','type'=>'Gym','coach'=>'Trenér Novák','location'=>'Posilovna A','start'=>date('Y-m-03').' 09:00','end'=>date('Y-m-03').' 10:30','notes'=>'Cíl: zvýšení síly nohou a trupu.','exercises'=>[['name'=>'Dřepy s činkou','sets'=>4,'reps'=>6,'weight'=>100,'rest'=>120]]],
  ['id'=>2,'title'=>'Sprinty','type'=>'Sprint','coach'=>'Trenérka Malá','location'=>'Atletický ovál','start'=>date('Y-m-05').' 15:00','end'=>date('Y-m-05').' 16:00','notes'=>'Rozvoj výbušnosti.','exercises'=>[['name'=>'Sprint 100m','distance'=>'100m','reps'=>5,'rest'=>120]]],
  ['id'=>3,'title'=>'Regenerace','type'=>'Recovery','coach'=>'Fyzio Hrubý','location'=>'Wellness','start'=>date('Y-m-10').' 10:00','end'=>date('Y-m-10').' 11:00','notes'=>'Kompenzační cvičení.','exercises'=>[['name'=>'Strečink','duration'=>20,'tools'=>'podložka']]],
  // Additional test events across several days
  ['id'=>4,'title'=>'Mobility session','type'=>'Recovery','coach'=>'Karel','location'=>'Studio','start'=>date('Y-m-02').' 08:00','end'=>date('Y-m-02').' 09:00','notes'=>'Ranní mobilita.','exercises'=>[['name'=>'Mobility','duration'=>30]]],
  ['id'=>5,'title'=>'HIIT','type'=>'Sprint','coach'=>'Eliška','location'=>'Park','start'=>date('Y-m-07').' 18:00','end'=>date('Y-m-07').' 18:30','notes'=>'Krátký intenzivní trénink.','exercises'=>[['name'=>'Intervaly','reps'=>10]]],
  ['id'=>6,'title'=>'Yoga chill','type'=>'Recovery','coach'=>'Anna','location'=>'Studio B','start'=>date('Y-m-12').' 19:00','end'=>date('Y-m-12').' 20:00','notes'=>'Regenerační jóga.','exercises'=>[['name'=>'Jóga','duration'=>60]]],
  // Nearby days for demonstrations across week boundaries
  ['id'=>7,'title'=>'Tempo run','type'=>'Sprint','coach'=>'Pavel','location'=>'Trať','start'=>date('Y-m-25').' 17:00','end'=>date('Y-m-25').' 18:00','notes'=>'Tempo běh.','exercises'=>[['name'=>'Tempo','distance'=>'5km']]],
  ['id'=>8,'title'=>'Strength B','type'=>'Gym','coach'=>'Novák','location'=>'Posilovna B','start'=>date('Y-m-18').' 16:00','end'=>date('Y-m-18').' 17:30','notes'=>'Dolní část těla.','exercises'=>[['name'=>'Deadlift','sets'=>5,'reps'=>5]]],
];

// Directory and file for saved workouts
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/trainings.json';

// Handle POST to add a workout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_workout') {
  // basic sanitization
  $date = $_POST['date'] ?? date('Y-m-d');
  $startTime = $_POST['start_time'] ?? '09:00';
  $endTime = $_POST['end_time'] ?? '10:00';
  $title = trim($_POST['title'] ?? 'Workout');
  $coach = trim($_POST['coach'] ?? '');
  $location = trim($_POST['location'] ?? 'Gym');
  $notes = trim($_POST['notes'] ?? '');
  $type = 'Gym'; // store as Gym session
  // accept provided type if valid
  $validTypes = ['Gym','Sprint','Track'];
  if (!empty($_POST['type']) && in_array($_POST['type'], $validTypes, true)) {
    $type = $_POST['type'];
  }

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

  // If exercises were submitted from the modal, decode and attach them
  if (!empty($_POST['exercises_json'])) {
    $decoded = json_decode($_POST['exercises_json'], true);
    if (is_array($decoded)) {
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
  file_put_contents($dataFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  // Redirect to avoid form resubmission; preserve view params
  $qs = $_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : '';
  header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
  exit;
}

// Load saved trainings if present
$userTrainings = [];
if (file_exists($dataFile)) {
  $json = file_get_contents($dataFile);
  $userTrainings = json_decode($json, true) ?: [];
}

// Merge defaults and user-created trainings (user trainings last so they show as added)
$trainings = array_merge($defaultTrainings, $userTrainings);

$trainingByDay = [];
foreach ($trainings as $t) {
    $dayNum = (int)date('j', strtotime($t['start']));
    $trainingByDay[$dayNum][] = $t;
}
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
        <span class="mx-3 h5 mb-0"><?= strftime('%B %Y', $firstDay->getTimestamp()) ?></span>
        <a href="?view=month&month=<?= $month == 12 ? 1 : $month + 1 ?>&year=<?= $month == 12 ? $year + 1 : $year ?>" class="btn btn-outline-primary btn-sm">Další &raquo;</a>
      <?php else: ?>
        <?php
          $prevWeek = (clone $startOfWeek)->modify('-7 days');
          $nextWeek = (clone $startOfWeek)->modify('+7 days');
        ?>
        <a href="?view=week&date=<?= $prevWeek->format('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">&laquo; Předchozí</a>
        <span class="mx-3 h5 mb-0"><?= $startOfWeek->format('j. M Y') ?> - <?= $startOfWeek->modify('+6 days')->format('j. M Y') ?></span>
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
            echo "<td class='$isWeekend'><div class='day-number'>$day</div>";
            if (isset($trainingByDay[$day])) {
                foreach ($trainingByDay[$day] as $tr) {
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
                    if (isset($trainingByDay[$day])) {
                        foreach ($trainingByDay[$day] as $tr) {
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

  <!-- Add Workout Modal -->
  <div class="modal fade" id="addWorkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="action" value="add_workout">
          <div class="modal-header">
            <h5 class="modal-title">Přidat workout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Datum</label>
              <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="row">
              <div class="col">
                <label class="form-label">Začátek</label>
                <input type="time" name="start_time" class="form-control" value="09:00" required>
              </div>
              <div class="col">
                <label class="form-label">Konec</label>
                <input type="time" name="end_time" class="form-control" value="10:00" required>
              </div>
            </div>
            <div class="mb-2 mt-2">
              <label class="form-label">Název</label>
              <input type="text" name="title" class="form-control" placeholder="Název workoutu" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Typ</label>
              <select name="type" class="form-select">
                <option value="Gym">Gym</option>
                <option value="Sprint">Sprint</option>
                <option value="Track">Track</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Trenér</label>
              <input type="text" name="coach" class="form-control" placeholder="Trenér">
            </div>
            <div class="mb-2">
              <label class="form-label">Místo</label>
              <input type="text" name="location" class="form-control" placeholder="Místo">
            </div>
            <div class="mb-2">
              <label class="form-label">Poznámky</label>
              <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
            <hr>
            <h6>Seznam cviků</h6>
            <div class="row g-2 align-items-end">
              <div class="col-6">
                <label class="form-label">Cvik</label>
                <select id="exerciseSelect" class="form-select">
                  <option>Dřepy s činkou</option>
                  <option>Mrtvý tah</option>
                  <option>Bench press</option>
                  <option>Shyby</option>
                  <option>Výpady</option>
                  <option>Strečink</option>
                  <option>Sprint 100m</option>
                </select>
              </div>
              <div class="col-2">
                <label class="form-label">Série</label>
                <input id="exSets" type="number" class="form-control" value="3">
              </div>
              <div class="col-2">
                <label class="form-label">Opakování</label>
                <input id="exReps" type="number" class="form-control" value="8">
              </div>
              <div class="col-2">
                <label class="form-label">Váha</label>
                <input id="exWeight" type="number" class="form-control" value="0">
              </div>
            </div>
            <div class="mt-2 d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary" id="addExBtn">Přidat cvik</button>
              <small class="text-muted align-self-center">Klikni pro přidání cviku do seznamu</small>
            </div>
            <input type="hidden" name="exercises_json" id="exercises_json">
            <ul class="list-group mt-2" id="exerciseList"></ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
            <button type="submit" class="btn btn-primary">Uložit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

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
  document.getElementById('modalTitle').textContent = data.title;
  document.getElementById('modalType').textContent = data.type;
  document.getElementById('modalCoach').textContent = data.coach;
  document.getElementById('modalLocation').textContent = data.location;
  document.getElementById('modalTime').textContent =
    new Date(data.start).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) +
    " - " +
    new Date(data.end).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  document.getElementById('modalNotes').textContent = data.notes;

  const tbody = document.getElementById('exerciseTableBody');
  const thead = document.getElementById('exerciseTableHead');
  tbody.innerHTML = '';
  thead.innerHTML = '';

  if (!data.exercises || data.exercises.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Žádné záznamy</td></tr>';
  } else {
    let headers = [];
    let rows = '';

    switch (data.type) {
      case 'Gym':
        headers = ['Cvik', 'Série', 'Opakování', 'Váha (kg)', 'Pauza (s)'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.sets}</td><td>${ex.reps}</td><td>${ex.weight}</td><td>${ex.rest}</td></tr>
        `).join('');
        break;

      case 'Sprint':
        headers = ['Cvik', 'Vzdálenost', 'Opakování', 'Pauza (s)', 'Poznámka'];
        rows = data.exercises.map(ex => `
          <tr><td>${ex.name}</td><td>${ex.distance}</td><td>${ex.reps}</td><td>${ex.rest}</td><td>${ex.notes}</td></tr>
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

<script>
// Exercise builder for Add Workout modal
(function(){
  const exercises = [];
  const list = document.getElementById('exerciseList');
  const hidden = document.getElementById('exercises_json');

  function render(){
    if (!list) return;
    list.innerHTML = '';
    exercises.forEach((ex, idx)=>{
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.innerHTML = `<div><strong>${escapeHtml(ex.name)}</strong><div class="small text-muted">S:${ex.sets} R:${ex.reps} W:${ex.weight}</div></div><div><button data-idx="${idx}" class="btn btn-sm btn-outline-danger rm-ex">Odstranit</button></div>`;
      list.appendChild(li);
    });
    if (hidden) hidden.value = JSON.stringify(exercises);
  }

  function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

  const addBtn = document.getElementById('addExBtn');
  if (addBtn) addBtn.addEventListener('click', function(){
    const name = document.getElementById('exerciseSelect').value || '';
    const sets = parseInt(document.getElementById('exSets').value) || 0;
    const reps = parseInt(document.getElementById('exReps').value) || 0;
    const weight = parseFloat(document.getElementById('exWeight').value) || 0;
    if (!name) return;
    exercises.push({name, sets, reps, weight});
    // reset small fields
    document.getElementById('exSets').value = 3;
    document.getElementById('exReps').value = 8;
    document.getElementById('exWeight').value = 0;
    render();
  });

  if (list) list.addEventListener('click', function(e){
    if (e.target.classList.contains('rm-ex')){
      const idx = parseInt(e.target.getAttribute('data-idx'));
      if (!isNaN(idx)) exercises.splice(idx,1);
      render();
    }
  });

  // Clear builder when modal is hidden so adding next workout starts fresh
  const modal = document.getElementById('addWorkoutModal');
  if (modal) modal.addEventListener('hidden.bs.modal', function(){
    exercises.length = 0; render();
  });

})();
</script>

</body>

</html>