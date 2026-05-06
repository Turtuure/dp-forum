<?php
declare(strict_types=1);

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$pageTitle   = 'Forum';
$activePage  = 'forum';
$breadcrumbs = [];

ob_start();
?>
<div class="page-header">
  <div>
    <h1 class="page-header__title">Forum</h1>
    <p class="page-header__subtitle">Moderate reports, topics, and categories.</p>
  </div>
</div>

<?php
$active_kpi = null; // dashboard has no "active" sub-page
include __DIR__ . '/forum-kpi-strip.php';
?>

<!-- Recent activity card -->
<div class="data-explorer__panel" style="padding: 18px 22px;">
  <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 10px;">Recent activity</h3>
  <ul id="forum-recent-audit" style="list-style:none;padding:0;margin:0;">
    <li style="color: var(--text-muted); font-size: 13px;">Loading…</li>
  </ul>
  <a href="/backstage/forum/audit" class="btn btn--text" style="margin-top: 8px;">View all →</a>
</div>

<script src="/modules/forum/assets/backstage/forum-kpi-strip.js" defer></script>
<script src="/modules/forum/assets/backstage/forum-dashboard.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';
