<?php
// Subheader snippet using Bootstrap - intended to be included under a larger header (e.g., WP header)
?>
<div class="container-fluid bg-white border-bottom">
  <div class="container">
    <div class="row align-items-center py-2">
      <div class="col-auto">
        <!-- small logo / icon -->
        <img src="/img/logo.png" alt="Logo" style="height:36px;" onerror="this.style.display='none'">
      </div>
      <div class="col">
        <h5 class="mb-0">Podnadpis pro přihlášené uživatele</h5>
        <small class="text-muted">Krátká nápověda nebo rychlé odkazy</small>
      </div>
            <div class="col-auto">
              <?php
              // Determine the current path to highlight the active link
              $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
              // Normalize (remove trailing slash)
              $currentPath = rtrim($currentPath, '/');

              // Menu links: change to point to index.php, calender.php and metrics
              $links = [
                ['/testz/index.php', 'Domů', 'btn-outline-primary'],
                ['/testz/calender.php', 'Kalendář', 'btn-outline-secondary'],
                ['/testz/training_history.php', 'Historie tréninků', 'btn-outline-dark'],
                ['/testz/exercise_calculator.php', 'Kalkulačka cviků', 'btn-outline-dark'],
                ['/testz/metrics_history.php', 'Metriky', 'btn-outline-info'],
              ];
              ?>
              <div class="btn-group" role="group" aria-label="Quick links">
                <?php foreach ($links as [$href, $label, $styleClass]) :
                  $isActive = ($currentPath === rtrim($href, '/')) ? ' active' : '';
                  // For accessibility, add aria-current when active
                  $aria = $isActive ? ' aria-current="page"' : '';
                ?>
                  <a href="<?= $href ?>" class="btn btn-sm <?= $styleClass ?><?= $isActive ?>"<?= $aria ?>><?= $label ?></a>
                <?php endforeach; ?>
                <button class="btn btn-sm btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#metricsModal">Zapsat metriky</button>
              </div>
            </div>
    </div>
  </div>
</div>
<?php
// Include metrics modal so it's available on every page via the subheader
$metricsModal = __DIR__ . '/metrics_modal.php';
if (file_exists($metricsModal)) include $metricsModal;
?>
