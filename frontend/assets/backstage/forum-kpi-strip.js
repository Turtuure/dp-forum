/**
 * Forum KPI strip — fetches /api/backstage/forum.php?op=stats and populates
 * KPI values + sparklines on whatever page includes the strip.
 *
 * No recent-activity rendering — that's dashboard-only (forum-dashboard.js).
 */
(function () {
  'use strict';

  // Run only if the strip is present on the page.
  if (!document.querySelector('.kpis-grid .kpi-card[data-kpi="open_reports"]')) return;

  var KPI_COLORS = {
    open_reports: '#d97706',
    topics:       '#3b82f6',
    categories:   '#64748b',
    mod_actions:  '#16a34a',
  };

  fetch('/api/backstage/forum.php?op=stats')
    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (j) { render(j && j.data ? j.data : null); })
    .catch(function (e) { console.error('forum stats failed', e); });

  function render(data) {
    if (!data) return;
    document.querySelectorAll('.kpi-card').forEach(function (c) { c.classList.remove('is-loading'); });

    setKpi('open_reports', data.open_reports);
    setKpi('topics',       data.topics);
    setKpi('categories',   data.categories);
    setKpi('mod_actions',  data.mod_actions);

    initSpark('open_reports', data.open_reports.sparkline);
    initSpark('topics',       data.topics.sparkline);
    initSpark('categories',   data.categories.sparkline);
    initSpark('mod_actions',  data.mod_actions.sparkline);
  }

  function setKpi(id, payload) {
    var el = document.querySelector('.kpi-card[data-kpi="' + id + '"] .kpi-card__value');
    if (el && payload) el.textContent = String(payload.value);
  }

  function initSpark(id, points) {
    var el = document.getElementById('spark-' + id);
    if (el && window.Sparkline) window.Sparkline.init(el, points || [], KPI_COLORS[id]);
  }
})();
