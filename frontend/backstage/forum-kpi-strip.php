<?php
/**
 * Shared forum KPI strip.
 *
 * Renders 4 clickable KPI cards (Open reports / Topics / Categories / Mod actions).
 * Used by the forum dashboard AND each forum sub-page so users can navigate
 * between sub-pages without going back to the dashboard.
 *
 * Variables expected before include:
 * @var ?string $active_kpi  One of: open_reports|topics|categories|mod_actions|null.
 *                           When set, that card gets a brand-color border.
 * @var bool    $compact     Default false. When true, cards render without
 *                           sparklines (sub-pages use this — sparklines only
 *                           earn their place on dashboard pages).
 *
 * Sparkline values are populated by forum-kpi-strip.js (separate include).
 * That JS no-ops gracefully when no sparkline mount is present (compact mode).
 */
declare(strict_types=1);

$active_kpi = $active_kpi ?? null;
$compact    = $compact ?? false;

$iconBell   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>';
$iconChat   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
$iconFolder = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
$iconShield = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 12l2 2 4-4"/></svg>';

$kpis = [
    ['kpi_id' => 'open_reports', 'label' => 'Open reports', 'value' => '—', 'icon_html' => $iconBell,   'icon_variant' => 'amber', 'trend_label' => 'pending review',   'trend_direction' => 'warn',  'href' => '/backstage/forum/reports'],
    ['kpi_id' => 'topics',       'label' => 'Topics',        'value' => '—', 'icon_html' => $iconChat,   'icon_variant' => 'blue',  'trend_label' => 'new this month',   'trend_direction' => 'muted', 'href' => '/backstage/forum/topics'],
    ['kpi_id' => 'categories',   'label' => 'Categories',    'value' => '—', 'icon_html' => $iconFolder, 'icon_variant' => 'gray',  'trend_label' => 'visible',          'trend_direction' => 'muted', 'href' => '/backstage/forum/categories'],
    ['kpi_id' => 'mod_actions',  'label' => 'Mod actions',   'value' => '—', 'icon_html' => $iconShield, 'icon_variant' => 'green', 'trend_label' => 'last 30 days',     'trend_direction' => 'muted', 'href' => '/backstage/forum/audit'],
];
?>
<div class="kpis-grid">
  <?php foreach ($kpis as $kpi):
    $isActive = ($active_kpi !== null && $active_kpi === $kpi['kpi_id']);
    // Capture the partial output, then post-process to add active-state inline style.
    ob_start();
    daems_shared_partial('components/cards/kpi-card/kpi-card', $kpi);
    $cardHtml = ob_get_clean();
    if ($compact) {
      // Add the --compact modifier so the system CSS hides the sparkline area
      // and reduces min-height. The fetch-and-render JS is unaffected — it
      // no-ops when the sparkline mount is hidden / absent.
      $cardHtml = preg_replace(
        '/(class="kpi-card kpi-card--clickable)(")/',
        '$1 kpi-card--compact$2',
        $cardHtml,
        1
      );
    }
    if ($isActive) {
      // Inject inline style for active card — no system-CSS edit needed.
      $cardHtml = preg_replace(
        '/(class="kpi-card kpi-card--clickable[^"]*")/',
        '$1 style="border-color: var(--brand-primary); border-width: 2px;"',
        $cardHtml,
        1
      );
    }
    echo $cardHtml;
  endforeach; ?>
</div>
