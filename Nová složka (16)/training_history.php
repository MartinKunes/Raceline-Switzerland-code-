<?php
// Training history page - lists all trainings grouped by date
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/trainings.json';

$trainings = [];
if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $trainings = json_decode($json, true) ?: [];
}

// Filtering: support presets and custom range via GET
$preset = $_GET['preset'] ?? 'all';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$now = time();
$fromTs = null; $toTs = null;
switch ($preset) {
    case '7d':
        $fromTs = strtotime('-6 days', strtotime(date('Y-m-d'))); // include today
        $toTs = strtotime('23:59:59');
        break;
    case '30d':
        $fromTs = strtotime('-29 days', strtotime(date('Y-m-d')));
        $toTs = strtotime('23:59:59');
        break;
    case 'month':
        $fromTs = strtotime(date('Y-m-01'));
        $toTs = strtotime(date('Y-m-t 23:59:59'));
        break;
    case 'year':
        $fromTs = strtotime(date('Y-01-01'));
        $toTs = strtotime(date('Y-12-31 23:59:59'));
        break;
    case 'range':
        if ($from) $fromTs = strtotime($from . ' 00:00:00');
        if ($to) $toTs = strtotime($to . ' 23:59:59');
        break;
    default:
        // all
        $fromTs = null; $toTs = null;
}

// Apply filtering
$filtered = [];
foreach ($trainings as $t) {
    $ts = strtotime($t['start'] ?? '');
    if (!$ts) continue;
    $ok = true;
    if ($fromTs !== null && $ts < $fromTs) $ok = false;
    if ($toTs !== null && $ts > $toTs) $ok = false;
    if ($ok) $filtered[] = $t;
}

// Sort newest first
usort($filtered, function($a, $b){
    $ta = strtotime($a['start'] ?? '');
    $tb = strtotime($b['start'] ?? '');
    return $tb <=> $ta;
});

// Group by date (Y-m-d)
$byDate = [];
foreach ($filtered as $t) {
    $key = date('Y-m-d', strtotime($t['start'] ?? ''));
    $byDate[$key][] = $t;
}

function fmtDateNice($d) {
    $dt = strtotime($d);
    if (!$dt) return $d;
    return strftime('%A, %e. %B %Y', $dt);
}

// small map for pretty parameter labels
$paramLabels = [
    'sets' => 'Série', 'reps' => 'Opakování', 'pct1rm' => '%1RM', 'multiplier' => 'Multiplier',
    'distance' => 'Vzdálenost (m)', 'effort' => 'Effort', 'session_factor' => 'Session', 'rest_ratio' => 'Rest ratio',
    'recovery_ratio' => 'Recovery', 'gear_ratio' => 'Gear ratio', 'duration' => 'Délka', 'tools' => 'Pomůcky'
];

?><!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historie tréninků</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --bg:#0f1723; /* deep neutral background for framing (page body remains light) */
      --card-bg:#ffffff;
      --muted:#6b7280;
      --accent:#0ea5a4; /* tasteful teal accent */
      --accent-2:#7c3aed; /* secondary subtle purple */
      --glass: rgba(15,23,35,0.03);
    }
    body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color:#0b1220; background:#f6f7f9; }
    .history-card { border: none; border-radius:12px; background:var(--card-bg); box-shadow: 0 6px 18px rgba(11,18,32,0.06); overflow: hidden; }
    .card-header.date-header { background: transparent; border-bottom: 1px solid rgba(15,23,35,0.04); padding:18px 20px; }
    .card-body { padding: 14px 18px; }
    h3 { font-weight:600; letter-spacing: -0.02em; }
    .small-muted { font-size:0.9rem; color:var(--muted); }
    .param-badge { margin-right:8px; margin-bottom:8px; border-radius:999px; padding:6px 8px; font-size:0.82rem; }
    .table thead th { border-bottom: none; color: #4b5563; font-weight:600; }
    .table { background: transparent; }
    .table-hover tbody tr:hover { background: rgba(14,165,164,0.03); }
    .badge.bg-success { background: linear-gradient(180deg,#16a34a,#059669); box-shadow: 0 2px 6px rgba(5,150,105,0.12); }
    .btn-outline-primary { border-color: rgba(11,18,32,0.06); color: #0b1220; background: transparent; }
    .btn-outline-secondary { border-color: rgba(11,18,32,0.06); color: #0b1220; background: transparent; }
    .btn-link { color: var(--muted); }
    .card .card-body .card { border-radius:10px; box-shadow: 0 6px 18px rgba(11,18,32,0.04); border: 1px solid rgba(11,18,32,0.04); }
    .card .card-body .card .card-body { padding:10px; }
    @media (max-width: 767px) {
      .card .card-body .card { max-width: 100% !important; min-width: auto !important; }
      .table-responsive { overflow-x: auto; }
    }
    /* subtle inputs */
    .form-control, .form-select { border-radius:8px; border:1px solid rgba(11,18,32,0.06); }
  </style>
</head>
<body class="bg-light">
<?php
$subheader = __DIR__ . '/subheader.php';
if (file_exists($subheader)) include_once $subheader;
?>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Historie tréninků</h3>
      <div class="small-muted">Seřazeno podle data (nejnovější nahoře). Filtrujte pro vybraný rozsah.</div>
    </div>
    <div>
      <a href="/testz/calender.php" class="btn btn-outline-secondary">Zpět do kalendáře</a>
    </div>
  </div>

  <form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
      <label class="form-label small mb-0">Rychlý filtr</label>
      <select name="preset" class="form-select">
        <option value="all" <?php if($preset==='all') echo 'selected'; ?>>Vše</option>
        <option value="7d" <?php if($preset==='7d') echo 'selected'; ?>>Posledních 7 dní</option>
        <option value="30d" <?php if($preset==='30d') echo 'selected'; ?>>Posledních 30 dní</option>
        <option value="month" <?php if($preset==='month') echo 'selected'; ?>>Tento měsíc</option>
        <option value="year" <?php if($preset==='year') echo 'selected'; ?>>Tento rok</option>
        <option value="range" <?php if($preset==='range') echo 'selected'; ?>>Vlastní rozsah</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label small mb-0">Od</label>
      <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>">
    </div>
    <div class="col-auto">
      <label class="form-label small mb-0">Do</label>
      <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary">Filtruj</button>
      <a href="/testz/training_history.php" class="btn btn-link">Reset</a>
    </div>
  </form>

  <?php if (empty($byDate)): ?>
    <div class="alert alert-info">Žádné tréninky k zobrazení pro zvolený rozsah.</div>
  <?php else: ?>
    <?php foreach ($byDate as $date => $list):
        $dateTotalAu = 0.0; $dateTotalEx = 0;
        foreach ($list as $t) {
            if (isset($t['exercises']) && is_array($t['exercises'])) foreach ($t['exercises'] as $ex) if (isset($ex['au'])) $dateTotalAu += (float)$ex['au'];
            $dateTotalEx += isset($t['exercises']) && is_array($t['exercises']) ? count($t['exercises']) : 0;
        }
    ?>
      <div class="card mb-3 history-card">
        <div class="card-header d-flex justify-content-between align-items-center date-header">
          <div>
            <strong><?php echo htmlspecialchars(fmtDateNice($date)); ?></strong>
            <div class="small-muted"><?php echo count($list); ?> tréninků — <?php echo $dateTotalEx; ?> cviků — AU celkem: <span class="badge bg-info text-dark"><?php echo round($dateTotalAu,2); ?></span></div>
          </div>
          <div>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#date-<?php echo htmlspecialchars($date); ?>">Přepnout detaily</button>
          </div>
        </div>
  <div class="collapse" id="date-<?php echo htmlspecialchars($date); ?>">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:115px">Čas</th>
                    <th>Název</th>
                    <th style="width:110px">Typ</th>
                    <th style="width:140px">Trenér / Místo</th>
                    <th style="width:90px">Cviky</th>
                    <th style="width:120px">AU</th>
                    <th style="width:80px">Akce</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($list as $t):
                    $start = strtotime($t['start'] ?? '');
                    $time = $start ? date('H:i', $start) . ' - ' . date('H:i', strtotime($t['end'] ?? '')) : '';
                    $exCount = isset($t['exercises']) && is_array($t['exercises']) ? count($t['exercises']) : 0;
                    $tAu = 0.0;
                    if ($exCount) foreach ($t['exercises'] as $ex) if (isset($ex['au'])) $tAu += (float)$ex['au'];
                    $tAu = round($tAu,2);
                ?>
                  <tr>
                    <td style="white-space:nowrap"><?php echo htmlspecialchars($time); ?></td>
                    <td><?php echo htmlspecialchars($t['title'] ?? ''); ?><div class="small-muted mt-1"><?php echo htmlspecialchars($t['notes'] ?? ''); ?></div></td>
                    <td><?php echo htmlspecialchars($t['type'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($t['coach'] ?? ''); ?> <br><small class="text-muted"><?php echo htmlspecialchars($t['location'] ?? ''); ?></small></td>
                    <td><?php echo $exCount; ?></td>
                    <td><span class="badge bg-success"><?php echo $tAu; ?></span></td>
                    <td><a href="#" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('ex-row-<?php echo $t['id']; ?>').classList.toggle('d-none'); return false;">Detaily</a></td>
                  </tr>
                  <tr id="ex-row-<?php echo $t['id']; ?>" class="table-secondary d-none">
                    <td colspan="7" class="p-2">
                      <div class="row">
                        <div class="col-12">
                          <strong>Detaily cviků</strong>
                        </div>
                        <div class="col-12 mt-2">
                          <?php if ($exCount): ?>
                            <div class="d-flex flex-wrap">
                              <?php foreach ($t['exercises'] as $ex): ?>
                                <div class="card me-2 mb-2" style="min-width:220px; max-width:32%;">
                                  <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                      <div>
                                        <strong><?php echo htmlspecialchars($ex['name'] ?? ''); ?></strong>
                                        <div class="small-muted">Type: <?php echo htmlspecialchars($t['type'] ?? ''); ?></div>
                                      </div>
                                      <div><span class="badge bg-success">AU <?php echo isset($ex['au']) ? (float)$ex['au'] : '-'; ?></span></div>
                                    </div>
                                    <div class="mt-2">
                                      <?php
                                        $parts = [];
                                        // ordered keys for nicer display
                                        $keys = ['sets','reps','pct1rm','multiplier','distance','effort','session_factor','rest_ratio','recovery_ratio','gear_ratio','duration','tools','notes'];
                                        foreach ($keys as $k) {
                                          if (!isset($ex[$k])) continue;
                                          $label = $paramLabels[$k] ?? $k;
                                          $val = $ex[$k];
                                          if ($k === 'distance') $val = $val . ' m';
                                          if ($k === 'duration') $val = $val . ' min';
                                          $parts[] = '<span class="badge bg-light text-dark param-badge">' . htmlspecialchars($label) . ': ' . htmlspecialchars($val) . '</span>';
                                        }
                                        echo implode(' ', $parts);
                                      ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <div class="text-muted">Žádné cviky</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle collapse buttons accessibility: update button text when collapsed state changes
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const target = document.querySelector(btn.getAttribute('data-bs-target'));
      // bootstrap will handle show/hide; this is just cosmetic if needed
    });
  });
</script>

</body>
</html>
