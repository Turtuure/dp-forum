<?php
declare(strict_types=1);

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$pageTitle   = 'Forum topics';
$activePage  = 'forum';
$breadcrumbs = [['label' => 'Forum', 'url' => '/backstage/forum'], ['label' => 'Topics']];

ob_start();
?>
<div class="page-header">
  <div>
    <h1 class="page-header__title">Topics</h1>
    <p class="page-header__subtitle">Manage forum topics — pin, lock, delete.</p>
  </div>
</div>

<?php
$active_kpi = 'topics';
$compact    = true;
include __DIR__ . '/../forum-kpi-strip.php';
?>

<div class="data-explorer__panel">
  <div class="data-explorer__toolbar">
    <div class="data-explorer__seg" id="ft-status-filter" role="tablist">
      <button type="button" class="data-explorer__seg-btn is-active" data-status="all">All</button>
      <button type="button" class="data-explorer__seg-btn"           data-status="pinned">Pinned</button>
      <button type="button" class="data-explorer__seg-btn"           data-status="locked">Locked</button>
      <button type="button" class="data-explorer__seg-btn"           data-status="open">Open</button>
    </div>
    <select class="data-explorer__search" id="ft-category-filter" style="min-width:160px;">
      <option value="">All categories</option>
    </select>
    <input type="search" id="ft-search" class="data-explorer__search" placeholder="Search title…">
  </div>

  <table class="data-explorer__data">
    <thead>
      <tr>
        <th>Title</th><th>Category</th><th>Author</th><th>Replies</th><th>Status</th><th>Created</th><th></th>
      </tr>
    </thead>
    <tbody id="ft-tbody"></tbody>
  </table>

  <div id="ft-empty-mount" style="display:none;">
    <?php
      $svg_path  = '/modules/forum/assets/backstage/empty-state-topics.svg';
      $title     = 'No topics';
      $body      = 'No forum topics match this filter.';
      include DAEMS_SITE_PUBLIC . '/pages/shared/empty-state.php';
    ?>
  </div>
  <div id="ft-error-mount" style="display:none;"></div>
</div>

<script src="/modules/forum/assets/backstage/forum-kpi-strip.js" defer></script>
<script src="/modules/forum/assets/backstage/forum-topics-page.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';
