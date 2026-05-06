<?php
declare(strict_types=1);

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$pageTitle   = 'Forum categories';
$activePage  = 'forum';
$breadcrumbs = [['label' => 'Forum', 'url' => '/backstage/forum'], ['label' => 'Categories']];

ob_start();
?>
<div class="page-header">
  <div>
    <h1 class="page-header__title">Categories</h1>
    <p class="page-header__subtitle">Manage forum categories.</p>
  </div>
  <div>
    <button type="button" class="btn btn--primary" id="fc-add-btn">+ New category</button>
  </div>
</div>

<?php
$active_kpi = 'categories';
$compact    = true;
include __DIR__ . '/../forum-kpi-strip.php';
?>

<div class="data-explorer__panel">
  <div class="data-explorer__toolbar">
    <input type="search" id="fc-search" class="data-explorer__search" placeholder="Search name or slug…">
  </div>

  <table class="data-explorer__data">
    <thead>
      <tr><th>Name</th><th>Slug</th><th>Description</th><th>Topics</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody id="fc-tbody"></tbody>
  </table>

  <div id="fc-empty-mount" style="display:none;padding:32px 16px;text-align:center;color:var(--text-muted);">No categories yet.</div>
  <div id="fc-error-mount" style="display:none;"></div>
</div>

<script src="/modules/forum/assets/backstage/forum-kpi-strip.js" defer></script>
<script src="/modules/forum/assets/backstage/forum-categories-page.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';
