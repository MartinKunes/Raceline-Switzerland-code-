<!-- Metrics entry modal (can be included in subheader to open anywhere) -->
<div class="modal fade" id="metricsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/testz/metrics_history.php">
        <input type="hidden" name="action" value="add_metric">
        <div class="modal-header">
          <h5 class="modal-title">Zapsat denní metriky</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Datum</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Spánek (hodiny)</label>
            <input type="number" step="0.1" name="sleep" class="form-control" placeholder="např. 7.5">
          </div>
          <div class="mb-3">
            <label class="form-label">Nálada <span id="moodVal" class="badge bg-secondary ms-2">5</span></label>
            <input type="range" name="mood" id="mood" class="form-range" min="1" max="10" value="5">
          </div>
          <div class="mb-3">
            <label class="form-label">Svalovina / Soreness <span id="sorenessVal" class="badge bg-secondary ms-2">5</span></label>
            <input type="range" name="soreness" id="soreness" class="form-range" min="1" max="10" value="5">
          </div>
          <div class="mb-2">
            <label class="form-label">Resting HR (bpm)</label>
            <input type="number" name="resting_hr" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">HRV (ms)</label>
            <input type="number" name="hrv" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Únava <span id="fatigueVal" class="badge bg-secondary ms-2">5</span></label>
            <input type="range" name="fatigue" id="fatigue" class="form-range" min="1" max="10" value="5">
          </div>
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
document.addEventListener('DOMContentLoaded', function(){
  function wire(sliderId, displayId){
    const s = document.getElementById(sliderId);
    const d = document.getElementById(displayId);
    if (!s || !d) return;
    d.textContent = s.value;
    s.addEventListener('input', function(){ d.textContent = s.value; });
  }
  wire('mood','moodVal');
  wire('soreness','sorenessVal');
  wire('fatigue','fatigueVal');
});
</script>
