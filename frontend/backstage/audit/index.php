<?php
declare(strict_types=1);

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$pageTitle   = 'backstage.title.forum_audit';
$activePage  = 'forum';
$breadcrumbs = [['label' => 'Forum', 'url' => '/backstage/forum'], ['label' => 'Audit']];

ob_start();
?>
<div class="page-header">
  <div>
    <h1 class="page-header__title">Audit</h1>
    <p class="page-header__subtitle">Read-only log of moderation actions.</p>
  </div>
</div>

<?php
$active_kpi = 'mod_actions';
$compact    = true;
include __DIR__ . '/../forum-kpi-strip.php';
?>

<div class="data-explorer__panel">
  <div class="data-explorer__toolbar">
    <select class="data-explorer__search" id="fa-action-filter" name="action"
            aria-label="Filter by mod action" style="min-width: 160px;">
      <option value="">All actions</option>
      <option value="deleted">Deleted</option>
      <option value="locked">Locked</option>
      <option value="unlocked">Unlocked</option>
      <option value="pinned">Pinned</option>
      <option value="unpinned">Unpinned</option>
      <option value="edited">Edited</option>
      <option value="warned">Warned</option>
      <option value="category_created">Category created</option>
      <option value="category_updated">Category updated</option>
      <option value="category_deleted">Category deleted</option>
    </select>
    <select class="data-explorer__search" id="fa-range-filter" name="range"
            aria-label="Time range" style="min-width: 130px;">
      <option value="7">Last 7 days</option>
      <option value="30" selected>Last 30 days</option>
      <option value="all">All time</option>
    </select>
    <input type="search" id="fa-search" name="q" class="data-explorer__search"
           aria-label="Search by actor name" placeholder="Search actor…">
  </div>

  <table class="data-explorer__data">
    <thead>
      <tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>Reason</th></tr>
    </thead>
    <tbody id="fa-tbody"></tbody>
  </table>

  <div id="fa-empty-mount" style="display:none;padding:32px 16px;text-align:center;color:var(--text-muted);">No moderation actions in this period.</div>
  <div id="fa-error-mount" style="display:none;"></div>
  <div style="display:flex;justify-content:center;padding:14px 0;">
    <button class="btn btn--secondary" id="fa-load-more" style="display:none;">Load more</button>
  </div>
</div>

<script src="/modules/forum/assets/backstage/forum-kpi-strip.js" defer></script>
<script src="/modules/forum/assets/backstage/forum-audit-page.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';
