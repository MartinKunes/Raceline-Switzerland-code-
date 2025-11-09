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

              // Menu links: change to point to index.php and calender.php
              $links = [
                ['/testz/index.php', 'Domů', 'btn-outline-primary'],
                ['/testz/calender.php', 'Kalendář', 'btn-outline-secondary'],
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
              </div>
            </div>
    </div>
  </div>
</div>
