<?php
// Standalone Exercise Calculator — client-side builder and AU calculations
?><!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kalkulačka cviků</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial; background:#f6f7f9; color:#0b1220; }
    .calculator-card { border-radius:12px; background:#fff; box-shadow:0 8px 24px rgba(11,18,32,0.06); padding:18px; }
    .muted { color:#6b7280; }
    .param-badge { margin-right:8px; margin-bottom:8px; border-radius:999px; padding:6px 8px; font-size:0.82rem; background:#f3f4f6; color:#0b1220; }
    .au-pill { font-weight:600; color:#053e3e; background:linear-gradient(180deg,#d1fae5,#bbf7d0); padding:6px 10px; border-radius:999px; }
    .form-control, .form-select { border-radius:8px; border:1px solid rgba(11,18,32,0.06); }
    .exercise-card { border-radius:10px; border:1px solid rgba(11,18,32,0.04); box-shadow: 0 6px 18px rgba(11,18,32,0.03); padding:10px; margin-bottom:10px; background:#fff; }
    .controls { gap:10px; }
    @media (max-width:767px) { .controls { flex-direction:column; } }
  </style>
</head>
<body>
<?php
$sub = __DIR__ . '/subheader.php'; if (file_exists($sub)) include_once $sub;
?>
<div class="container my-4">
  <div class="calculator-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Kalkulačka cviků</h4>
        <div class="muted">Vytvoř si cvik, spočítej AU a exportuj JSON. Stejné výpočty jako v přidávání workoutu.</div>
      </div>
      <div class="text-end">
        <div class="small muted">Celkové AU</div>
        <div id="totalAu" class="au-pill">0</div>
      </div>
    </div>

    <div class="row g-2 align-items-end mb-2">
      <div class="col-md-4">
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
      <div class="col-md-2">
        <label class="form-label">Typ</label>
        <select id="typeSelect" class="form-select">
          <option value="Gym">Gym</option>
          <option value="Track">Track</option>
          <option value="Sprint">Sprint</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Série</label>
        <input id="exSets" type="number" class="form-control" value="3">
      </div>
      <div class="col-md-2">
        <label class="form-label">Opakování</label>
        <input id="exReps" type="number" class="form-control" value="8">
      </div>
      <div class="col-md-2 text-end">
        <button id="addExBtn" class="btn btn-primary mt-2">Přidat</button>
      </div>
    </div>

    <div id="gymFields" class="row g-2 align-items-end mb-2">
      <div class="col-md-3">
        <label class="form-label">%1RM</label>
        <input id="exPct1RM" type="number" class="form-control" value="70" min="0" max="100">
      </div>
      <div class="col-md-3">
        <label class="form-label">Multiplier</label>
        <input id="exMultiplier" type="number" step="0.1" class="form-control" value="1">
      </div>
    </div>

    <div id="trackFields" class="row g-2 align-items-end mb-2" style="display:none">
      <div class="col-md-3">
        <label class="form-label">Vzdálenost (m)</label>
        <input id="exDistance" type="number" class="form-control" value="100">
      </div>
      <div class="col-md-3">
        <label class="form-label">Track effort</label>
        <input id="exEffort" type="number" step="0.01" class="form-control" value="1">
      </div>
      <div class="col-md-3">
        <label class="form-label">Session factor</label>
        <input id="exSessionFactor" type="number" step="0.01" class="form-control" value="1">
      </div>
      <div class="col-md-3">
        <label class="form-label">Rest ratio</label>
        <input id="exRestRatio" type="number" step="0.01" class="form-control" value="1">
      </div>
    </div>

    <div id="sprintFields" class="row g-2 align-items-end mb-2" style="display:none">
      <div class="col-md-3">
        <label class="form-label">Vzdálenost (m)</label>
        <input id="exSprintDistance" type="number" class="form-control" value="100">
      </div>
      <div class="col-md-2">
        <label class="form-label">Effort</label>
        <input id="exSprintEffort" type="number" step="0.01" class="form-control" value="1">
      </div>
      <div class="col-md-2">
        <label class="form-label">Session</label>
        <input id="exSprintSession" type="number" step="0.01" class="form-control" value="1">
      </div>
      <div class="col-md-2">
        <label class="form-label">Recovery</label>
        <input id="exSprintRecovery" type="number" step="0.01" class="form-control" value="1">
      </div>
      <div class="col-md-2">
        <label class="form-label">Gear</label>
        <input id="exSprintGear" type="number" step="0.01" class="form-control" value="1">
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="d-flex gap-2">
        <button id="clearBtn" class="btn btn-outline-secondary">Vyčistit</button>
      </div>
      <div class="muted">Počet cviků: <span id="countEx">0</span></div>
    </div>

    <hr>
    <div id="exerciseList" class="mt-2"></div>
  </div>
</div>

<script>
(function(){
  const exercises = [];
  const list = document.getElementById('exerciseList');
  const totalEl = document.getElementById('totalAu');
  const countEl = document.getElementById('countEx');

  function calcTotal(){
    const total = exercises.reduce((s, ex)=> s + (parseFloat(ex.au) || 0), 0);
    totalEl.textContent = +(total.toFixed(2));
    countEl.textContent = exercises.length;
  }

  function render(){
    list.innerHTML = '';
    exercises.forEach((ex, idx)=>{
      const div = document.createElement('div');
      div.className = 'exercise-card d-flex justify-content-between align-items-start';
      let left = document.createElement('div');
      let right = document.createElement('div');
      left.innerHTML = `<div><strong>${escapeHtml(ex.name)}</strong><div class='muted small'>${escapeHtml(ex.type || '')}</div></div>`;
      const params = [];
      ['sets','reps','pct1rm','multiplier','distance','effort','session_factor','rest_ratio','recovery_ratio','gear_ratio','duration','tools','notes'].forEach(k=>{
        if (typeof ex[k] !== 'undefined' && ex[k] !== null && ex[k] !== '') {
          let label = k;
          switch(k){ case 'sets': label='Série'; break; case 'reps': label='Opakování'; break; case 'pct1rm': label='%1RM'; break; case 'multiplier': label='Multiplier'; break; case 'distance': label='Vzdálenost'; break; case 'effort': label='Effort'; break; case 'session_factor': label='Session'; break; case 'rest_ratio': label='Rest ratio'; break; case 'recovery_ratio': label='Recovery'; break; case 'gear_ratio': label='Gear'; break; case 'duration': label='Délka'; break; case 'tools': label='Pomůcky'; break; }
          let val = ex[k]; if (k==='distance') val = val + ' m'; if (k==='duration') val = val + ' min';
          params.push(`<span class="param-badge">${label}: ${escapeHtml(val)}</span>`);
        }
      });
      left.innerHTML += '<div class="mt-2">' + params.join(' ') + '</div>';
      right.innerHTML = `<div class="text-end"><div class="au-pill">AU ${ex.au ?? ''}</div><div class="mt-2"><button data-idx="${idx}" class="btn btn-sm btn-outline-danger rm">Odstranit</button></div></div>`;
      div.appendChild(left); div.appendChild(right); list.appendChild(div);
    });
    calcTotal();
  }

  function escapeHtml(s){ return (s||'').toString().replace(/[&<>"]+/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

  const addBtn = document.getElementById('addExBtn');
  addBtn.addEventListener('click', function(e){
    e.preventDefault();
    const name = document.getElementById('exerciseSelect').value || '';
    const sets = parseFloat(document.getElementById('exSets').value) || 0;
    const reps = parseFloat(document.getElementById('exReps').value) || 0;
    const type = document.getElementById('typeSelect').value || 'Gym';
    if (!name) return;
    if (type === 'Gym'){
      const pct1rm = parseFloat(document.getElementById('exPct1RM').value) || 0;
      const multiplier = parseFloat(document.getElementById('exMultiplier').value) || 1;
      const au = +(sets * reps * (pct1rm/100) * multiplier).toFixed(2);
      exercises.push({name,type,sets,reps,pct1rm,multiplier,au});
    } else if (type === 'Track'){
      const distance = parseFloat(document.getElementById('exDistance').value) || 0;
      const effort = parseFloat(document.getElementById('exEffort').value) || 1;
      const sessionFactor = parseFloat(document.getElementById('exSessionFactor').value) || 1;
      const restRatio = parseFloat(document.getElementById('exRestRatio').value) || 1;
      const au = +((distance * (effort * sessionFactor) * restRatio * sets * reps / 6)).toFixed(2);
      exercises.push({name,type,distance,effort,session_factor:sessionFactor,rest_ratio:restRatio,sets,reps,au});
    } else if (type === 'Sprint'){
      const distance = parseFloat(document.getElementById('exSprintDistance').value) || 0;
      const effort = parseFloat(document.getElementById('exSprintEffort').value) || 1;
      const sessionFactor = parseFloat(document.getElementById('exSprintSession').value) || 1;
      const recoveryRatio = parseFloat(document.getElementById('exSprintRecovery').value) || 1;
      const gearRatio = parseFloat(document.getElementById('exSprintGear').value) || 1;
      const au = +((distance * (effort * sessionFactor) * recoveryRatio * sets * reps * gearRatio / 4)).toFixed(2);
      exercises.push({name,type,distance,effort,session_factor:sessionFactor,recovery_ratio:recoveryRatio,gear_ratio:gearRatio,sets,reps,au});
    }
    render();
  });

  list.addEventListener('click', function(e){
    if (e.target.classList.contains('rm')){
      const idx = parseInt(e.target.getAttribute('data-idx'));
      if (!isNaN(idx)) { exercises.splice(idx,1); render(); }
    }
  });

  document.getElementById('clearBtn').addEventListener('click', function(e){ e.preventDefault(); exercises.length=0; render(); });

  // export button removed — copying JSON intentionally disabled per user request

  // type select toggles
  const typeSelect = document.getElementById('typeSelect');
  const gymFields = document.getElementById('gymFields');
  const trackFields = document.getElementById('trackFields');
  const sprintFields = document.getElementById('sprintFields');
  function updateFields(){
    const t = typeSelect.value;
    gymFields.style.display = t==='Gym' ? '' : 'none';
    trackFields.style.display = t==='Track' ? '' : 'none';
    sprintFields.style.display = t==='Sprint' ? '' : 'none';
  }
  typeSelect.addEventListener('change', updateFields);
  updateFields();

})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
