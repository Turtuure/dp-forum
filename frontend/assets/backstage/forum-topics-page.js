/**
 * Forum topics admin — table with pin/lock/delete actions via confirm-dialog.
 *
 * Endpoints (via proxy):
 *   GET  /api/backstage/forum.php?op=topics_list&...
 *   POST /api/backstage/forum.php?op=topic_pin&id=...
 *   POST /api/backstage/forum.php?op=topic_unpin&id=...
 *   POST /api/backstage/forum.php?op=topic_lock&id=...
 *   POST /api/backstage/forum.php?op=topic_unlock&id=...
 *   POST /api/backstage/forum.php?op=topic_delete&id=...
 */
(function () {
  'use strict';

  var els = {
    tbody:    document.getElementById('ft-tbody'),
    seg:      document.getElementById('ft-status-filter'),
    catFilter:document.getElementById('ft-category-filter'),
    search:   document.getElementById('ft-search'),
    empty:    document.getElementById('ft-empty-mount'),
    error:    document.getElementById('ft-error-mount'),
  };
  if (!els.tbody) return;

  var state = { status: 'all', categoryId: '', q: '', rows: [] };

  function load() {
    skeleton();
    var qs = '?op=topics_list&limit=100';
    fetch('/api/backstage/forum.php' + qs)
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (j) { state.rows = (j && j.data) || []; render(); populateCategorySelect(); })
      .catch(function (err) { renderError(err); });
  }

  function skeleton() {
    els.tbody.innerHTML = '';
    for (var i = 0; i < 5; i++) {
      els.tbody.innerHTML +=
        '<tr class="data-explorer__skeleton">' +
        '<td><span class="skeleton--text" style="width:70%"></span></td>' +
        '<td><span class="skeleton--text" style="width:50%"></span></td>' +
        '<td><span class="skeleton--text" style="width:40%"></span></td>' +
        '<td><span class="skeleton--text" style="width:30%"></span></td>' +
        '<td><span class="skeleton--pill"></span></td>' +
        '<td><span class="skeleton--text" style="width:50%"></span></td>' +
        '<td></td></tr>';
    }
    if (els.empty) els.empty.style.display = 'none';
    if (els.error) els.error.style.display = 'none';
  }

  function populateCategorySelect() {
    var cats = {};
    state.rows.forEach(function (r) { if (r.category_id) cats[r.category_id] = r.category_name || r.category_id; });
    var current = els.catFilter.value;
    els.catFilter.innerHTML = '<option value="">All categories</option>' +
      Object.keys(cats).map(function (k) { return '<option value="' + esc(k) + '">' + esc(cats[k]) + '</option>'; }).join('');
    els.catFilter.value = current;
  }

  function filtered() {
    var q = state.q.toLowerCase();
    return state.rows.filter(function (r) {
      if (state.status === 'pinned' && !r.is_pinned) return false;
      if (state.status === 'locked' && !r.is_locked) return false;
      if (state.status === 'open'   && (r.is_pinned || r.is_locked)) return false;
      if (state.categoryId && r.category_id !== state.categoryId) return false;
      if (q && (r.title || '').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
  }

  function render() {
    var rows = filtered();
    if (!rows.length) {
      els.tbody.innerHTML = '';
      if (els.empty) els.empty.style.display = '';
      return;
    }
    if (els.empty) els.empty.style.display = 'none';
    els.tbody.innerHTML = rows.map(rowHtml).join('');
    Array.from(els.tbody.querySelectorAll('[data-action]')).forEach(function (b) {
      b.addEventListener('click', function () { onAction(b.getAttribute('data-action'), b.getAttribute('data-id'), b.getAttribute('data-title')); });
    });
  }

  function rowHtml(r) {
    var statusPills = [];
    if (r.is_pinned) statusPills.push('<span class="pill pill--featured">Pinned</span>');
    if (r.is_locked) statusPills.push('<span class="pill pill--archived">Locked</span>');
    if (!statusPills.length) statusPills.push('<span class="pill pill--draft">Open</span>');

    return '<tr class="row" data-id="' + esc(r.id) + '">' +
           '<td><strong>' + esc(r.title || '') + '</strong></td>' +
           '<td>' + esc(r.category_name || r.category_id || '') + '</td>' +
           '<td>' + esc(r.author_name || r.author_id || '') + '</td>' +
           '<td>' + (r.post_count || 0) + '</td>' +
           '<td>' + statusPills.join(' ') + '</td>' +
           '<td>' + esc((r.created_at || '').slice(0, 10)) + '</td>' +
           '<td class="data-explorer__actions">' +
           (r.is_pinned
             ? '<button class="btn btn--icon" data-action="unpin"  data-id="' + esc(r.id) + '" data-title="' + esc(r.title || '') + '" title="Unpin">📌</button>'
             : '<button class="btn btn--icon" data-action="pin"    data-id="' + esc(r.id) + '" data-title="' + esc(r.title || '') + '" title="Pin">📌</button>') +
           (r.is_locked
             ? '<button class="btn btn--icon" data-action="unlock" data-id="' + esc(r.id) + '" data-title="' + esc(r.title || '') + '" title="Unlock">🔒</button>'
             : '<button class="btn btn--icon" data-action="lock"   data-id="' + esc(r.id) + '" data-title="' + esc(r.title || '') + '" title="Lock">🔒</button>') +
           '<button class="btn btn--icon" data-action="delete" data-id="' + esc(r.id) + '" data-title="' + esc(r.title || '') + '" title="Delete">🗑</button>' +
           '</td></tr>';
  }

  function renderError(err) {
    els.tbody.innerHTML = '';
    if (els.error) {
      els.error.style.display = '';
      els.error.innerHTML =
        '<div class="error-state" role="alert">' +
        '<svg class="error-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 9v4M12 17h.01M3.6 18l8.4-14 8.4 14H3.6z"/></svg>' +
        '<div><div class="error-state__title">Could not load topics</div>' +
        '<div class="error-state__message">' + esc(err && err.message || 'Network error') + '</div></div>' +
        '<div class="error-state__actions"><button class="btn btn--primary" id="ft-retry">Retry</button></div></div>';
      var b = document.getElementById('ft-retry'); if (b) b.addEventListener('click', load);
    }
  }

  function onAction(action, id, title) {
    var labels = {
      pin:    {title: 'Pin topic?',    body: 'Pinned topics appear at the top of the category.', confirm: 'Pin',    danger: false},
      unpin:  {title: 'Unpin topic?',  body: 'The topic will no longer be pinned.',              confirm: 'Unpin',  danger: false},
      lock:   {title: 'Lock topic?',   body: 'No new replies can be posted until unlocked.',     confirm: 'Lock',   danger: false},
      unlock: {title: 'Unlock topic?', body: 'Replies will be allowed again.',                   confirm: 'Unlock', danger: false},
      delete: {title: 'Delete topic?', body: '"' + (title || '') + '" and all its posts will be deleted. This cannot be undone.', confirm: 'Delete', danger: true},
    };
    var L = labels[action];
    if (!L) return;
    window.ConfirmDialog.open({ title: L.title, body: L.body, confirmLabel: L.confirm, danger: L.danger })
      .then(function (ok) {
        if (!ok) return;
        var op = 'topic_' + action;
        fetch('/api/backstage/forum.php?op=' + op + '&id=' + encodeURIComponent(id), { method: 'POST' })
          .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); load(); })
          .catch(function (e) { alert('Action failed: ' + e.message); });
      });
  }

  els.seg.addEventListener('click', function (e) {
    var b = e.target.closest('[data-status]'); if (!b) return;
    Array.from(els.seg.querySelectorAll('[data-status]')).forEach(function (x) { x.classList.remove('is-active'); });
    b.classList.add('is-active');
    state.status = b.getAttribute('data-status');
    render();
  });
  els.catFilter.addEventListener('change', function () { state.categoryId = els.catFilter.value; render(); });
  els.search.addEventListener('input', function () { state.q = els.search.value || ''; render(); });

  function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  load();
})();
