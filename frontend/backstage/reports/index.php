<?php
declare(strict_types=1);

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$pageTitle   = 'Forum reports';
$activePage  = 'forum';
$breadcrumbs = [['label' => 'Forum', 'url' => '/backstage/forum'], ['label' => 'Reports']];

ob_start();
?>
<div class="page-header">
  <div>
    <h1 class="page-header__title">Reports</h1>
    <p class="page-header__subtitle">Review and resolve moderation reports.</p>
  </div>
</div>

<?php
$active_kpi = 'open_reports';
$compact    = true;
include __DIR__ . '/../forum-kpi-strip.php';
?>

<div class="data-explorer__panel">
  <div class="data-explorer__toolbar">
    <div class="data-explorer__seg" id="fr-status-filter" role="tablist">
      <button type="button" class="data-explorer__seg-btn"           data-status="all">All</button>
      <button type="button" class="data-explorer__seg-btn is-active" data-status="open">Open</button>
      <button type="button" class="data-explorer__seg-btn"           data-status="resolved">Resolved</button>
      <button type="button" class="data-explorer__seg-btn"           data-status="dismissed">Dismissed</button>
    </div>
    <select class="data-explorer__search" id="fr-target-filter" style="min-width: 130px;">
      <option value="">All types</option>
      <option value="post">Posts</option>
      <option value="topic">Topics</option>
    </select>
    <input type="search" id="fr-search" class="data-explorer__search" placeholder="Search…">
  </div>

  <div id="fr-list-mount" style="padding: 12px 0;"></div>
  <div id="fr-empty-mount" style="display:none;">
    <?php
      $svg_path  = '/modules/forum/assets/backstage/empty-state-reports.svg';
      $title     = 'No reports';
      $body      = 'Nothing flagged in this view.';
      include DAEMS_SITE_PUBLIC . '/pages/shared/empty-state.php';
    ?>
  </div>
  <div id="fr-error-mount" style="display:none;"></div>
</div>

<link rel="stylesheet" href="/modules/forum/assets/backstage/forum.css">
<script src="/modules/forum/assets/backstage/forum-kpi-strip.js" defer></script>
<script src="/modules/forum/assets/backstage/forum-reports-page.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';
