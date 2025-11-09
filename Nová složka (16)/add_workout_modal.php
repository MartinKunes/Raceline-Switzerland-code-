<!-- Add Workout Modal (extracted fragment) -->
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
            <div id="gymFields" class="d-flex gap-2">
              <div class="col-2">
                <label class="form-label">%1RM</label>
                <input id="exPct1RM" type="number" class="form-control" value="70" min="0" max="100">
              </div>
              <div class="col-2">
                <label class="form-label">Multiplier</label>
                <input id="exMultiplier" type="number" step="0.1" class="form-control" value="1">
              </div>
            </div>
          </div>
          <div id="trackFields" class="row g-2 mt-2 align-items-end">
            <div class="col-3">
              <label class="form-label">Vzdálenost (m)</label>
              <input id="exDistance" type="number" class="form-control" value="100">
            </div>
            <div class="col-3">
              <label class="form-label">Track effort (factor)</label>
              <input id="exEffort" type="number" step="0.01" class="form-control" value="1">
            </div>
            <div class="col-3">
              <label class="form-label">Session factor</label>
              <input id="exSessionFactor" type="number" step="0.01" class="form-control" value="1">
            </div>
            <div class="col-3">
              <label class="form-label">Rest ratio</label>
              <input id="exRestRatio" type="number" step="0.01" class="form-control" value="1">
            </div>
          </div>
          <div id="sprintFields" class="row g-2 mt-2 align-items-end d-none">
            <div class="col-3">
              <label class="form-label">Vzdálenost (m)</label>
              <input id="exSprintDistance" type="number" class="form-control" value="100">
            </div>
            <div class="col-3">
              <label class="form-label">Effort factor</label>
              <input id="exSprintEffort" type="number" step="0.01" class="form-control" value="1">
            </div>
            <div class="col-2">
              <label class="form-label">Session factor</label>
              <input id="exSprintSession" type="number" step="0.01" class="form-control" value="1">
            </div>
            <div class="col-2">
              <label class="form-label">Recovery ratio</label>
              <input id="exSprintRecovery" type="number" step="0.01" class="form-control" value="1">
            </div>
            <div class="col-2">
              <label class="form-label">Gear ratio</label>
              <input id="exSprintGear" type="number" step="0.01" class="form-control" value="1">
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

<script>
// Exercise builder for Add Workout modal (moved here with modal)
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
      let metaHtml = '';
      if (typeof ex.pct1rm !== 'undefined' || typeof ex.multiplier !== 'undefined') {
        // Gym entry
        metaHtml = `<span class="me-2">S:${ex.sets} R:${ex.reps}</span><span class="me-2">%1RM:${ex.pct1rm ?? ''}</span><span class="me-2">M:${ex.multiplier ?? ''}</span>`;
      } else if (typeof ex.gear_ratio !== 'undefined' || typeof ex.recovery_ratio !== 'undefined') {
        // Sprint entry
        metaHtml = `<span class="me-2">D:${ex.distance}m</span><span class="me-2">Eff:${ex.effort ?? ''}</span><span class="me-2">Sf:${ex.session_factor ?? ''}</span><span class="me-2">Rec:${ex.recovery_ratio ?? ''}</span><span class="me-2">G:${ex.gear_ratio ?? ''}</span><span class="me-2">S:${ex.sets} R:${ex.reps}</span>`;
      } else if (typeof ex.distance !== 'undefined') {
        // Track entry
        metaHtml = `<span class="me-2">D:${ex.distance}m</span><span class="me-2">Eff:${ex.effort ?? ''}</span><span class="me-2">Sf:${ex.session_factor ?? ''}</span><span class="me-2">Rr:${ex.rest_ratio ?? ''}</span><span class="me-2">S:${ex.sets} R:${ex.reps}</span>`;
      } else {
        metaHtml = `<span class="me-2">S:${ex.sets ?? ''}</span><span class="me-2">R:${ex.reps ?? ''}</span>`;
      }
      const auBadge = `<span class="badge bg-success ms-2">AU ${ex.au ?? ''}</span>`;
      li.innerHTML = `<div><strong>${escapeHtml(ex.name)}</strong><div class="small text-muted">${metaHtml} ${auBadge}</div></div><div><button data-idx="${idx}" class="btn btn-sm btn-outline-danger rm-ex">Odstranit</button></div>`;
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
    const type = document.querySelector('select[name="type"]').value || 'Gym';
    if (!name) return;
    if (type === 'Gym') {
      const pct1rm = parseFloat(document.getElementById('exPct1RM').value) || 0;
      const multiplier = parseFloat(document.getElementById('exMultiplier').value) || 1;
      const au = +(sets * reps * (pct1rm/100) * multiplier).toFixed(2);
      exercises.push({name, sets, reps, pct1rm, multiplier, au});
    } else if (type === 'Track') {
      const distance = parseFloat(document.getElementById('exDistance').value) || 0;
      const effort = parseFloat(document.getElementById('exEffort').value) || 1;
      const sessionFactor = parseFloat(document.getElementById('exSessionFactor').value) || 1;
      const restRatio = parseFloat(document.getElementById('exRestRatio').value) || 1;
      // AU = distance x (effort x session) x rest ratio x sets x reps /6
      const au = +((distance * (effort * sessionFactor) * restRatio * sets * reps / 6)).toFixed(2);
      exercises.push({name, distance, effort, session_factor: sessionFactor, rest_ratio: restRatio, sets, reps, au});
    } else if (type === 'Sprint') {
      const distance = parseFloat(document.getElementById('exSprintDistance').value) || 0;
      const effort = parseFloat(document.getElementById('exSprintEffort').value) || 1;
      const sessionFactor = parseFloat(document.getElementById('exSprintSession').value) || 1;
      const recoveryRatio = parseFloat(document.getElementById('exSprintRecovery').value) || 1;
      const gearRatio = parseFloat(document.getElementById('exSprintGear').value) || 1;
      // AU = distance x (effort x session) x recovery ratio x sets x reps x gear ratio /4
      const au = +((distance * (effort * sessionFactor) * recoveryRatio * sets * reps * gearRatio / 4)).toFixed(2);
      exercises.push({name, distance, effort, session_factor: sessionFactor, recovery_ratio: recoveryRatio, gear_ratio: gearRatio, sets, reps, au});
    } else {
      exercises.push({name, sets, reps});
    }
    // reset small fields
    document.getElementById('exSets').value = 3;
    document.getElementById('exReps').value = 8;
    // reset both groups
    if (document.getElementById('exPct1RM')) document.getElementById('exPct1RM').value = 70;
    if (document.getElementById('exMultiplier')) document.getElementById('exMultiplier').value = 1;
    if (document.getElementById('exDistance')) document.getElementById('exDistance').value = 100;
    if (document.getElementById('exEffort')) document.getElementById('exEffort').value = 1;
    if (document.getElementById('exSessionFactor')) document.getElementById('exSessionFactor').value = 1;
    if (document.getElementById('exRestRatio')) document.getElementById('exRestRatio').value = 1;
    if (document.getElementById('exSprintDistance')) document.getElementById('exSprintDistance').value = 100;
    if (document.getElementById('exSprintEffort')) document.getElementById('exSprintEffort').value = 1;
    if (document.getElementById('exSprintSession')) document.getElementById('exSprintSession').value = 1;
    if (document.getElementById('exSprintRecovery')) document.getElementById('exSprintRecovery').value = 1;
    if (document.getElementById('exSprintGear')) document.getElementById('exSprintGear').value = 1;
    render();
  });

  // Toggle visible input groups based on selected workout type
  const typeSelect = document.querySelector('select[name="type"]');
  const gymFields = document.getElementById('gymFields');
  const trackFields = document.getElementById('trackFields');
  const sprintFields = document.getElementById('sprintFields');
  function updateFieldVisibility(){
    const t = typeSelect.value;
    if (t === 'Gym') {
      if (gymFields) { gymFields.classList.remove('d-none'); }
      if (trackFields) { trackFields.classList.add('d-none'); }
      if (sprintFields) { sprintFields.classList.add('d-none'); }
    } else if (t === 'Track') {
      if (gymFields) { gymFields.classList.add('d-none'); }
      if (trackFields) { trackFields.classList.remove('d-none'); }
      if (sprintFields) { sprintFields.classList.add('d-none'); }
    } else {
      if (gymFields) { gymFields.classList.add('d-none'); }
      if (trackFields) { trackFields.classList.add('d-none'); }
      if (sprintFields) { sprintFields.classList.remove('d-none'); }
    }
  }
  if (typeSelect) {
    typeSelect.addEventListener('change', updateFieldVisibility);
    updateFieldVisibility();
  }

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
