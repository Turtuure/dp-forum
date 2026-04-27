/**
 * Forum dashboard — recent-activity card.
 * KPI loading lives in forum-kpi-strip.js (shared with sub-pages).
 */
(function () {
  'use strict';

  if (!document.getElementById('forum-recent-audit')) return;

  fetch('/api/backstage/forum.php?op=stats')
    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (j) { renderRecent(j && j.data && j.data.recent_audit ? j.data.recent_audit : []); })
    .catch(function (e) { console.error('forum recent activity failed', e); });

  function renderRecent(rows) {
    var ul = document.getElementById('forum-recent-audit');
    if (!ul) return;
    if (!rows.length) {
      ul.innerHTML = '<li style="color: var(--text-muted); font-size: 13px;">No recent moderation actions.</li>';
      return;
    }
    ul.innerHTML = rows.map(function (r) {
      var when = (r.when || '').slice(0, 16).replace('T', ' ');
      return '<li style="padding: 6px 0; border-bottom: 1px solid var(--surface-border); font-size: 13px;">' +
             '<span style="color: var(--text-muted);">' + when + '</span> · ' +
             '<span class="pill pill--archived" style="margin: 0 4px;">' + esc(r.action) + '</span> ' +
             esc(r.target_type) +
             (r.reason ? ' · <em style="color: var(--text-muted);">' + esc(r.reason) + '</em>' : '') +
             '</li>';
    }).join('');
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
