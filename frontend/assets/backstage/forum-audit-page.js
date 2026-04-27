/**
 * Forum audit log — read-only table with filters + pagination.
 * Endpoint: GET /api/backstage/forum.php?op=audit_list&limit=50&offset=N&action=&since=
 */
(function () {
  'use strict';

  var els = {
    tbody:    document.getElementById('fa-tbody'),
    actionFilter: document.getElementById('fa-action-filter'),
    rangeFilter:  document.getElementById('fa-range-filter'),
    search:   document.getElementById('fa-search'),
    empty:    document.getElementById('fa-empty-mount'),
    error:    document.getElementById('fa-error-mount'),
    more:     document.getElementById('fa-load-more'),
  };
  if (!els.tbody) return;

  var PAGE = 50;
  var state = { rows: [], offset: 0, exhausted: false, action: '', range: '30', q: '' };

  function load(reset) {
    if (reset) { state.rows = []; state.offset = 0; state.exhausted = false; els.tbody.innerHTML = ''; }
    skeleton();
    var params = ['op=audit_list', 'limit=' + PAGE, 'offset=' + state.offset];
    if (state.action) params.push('action=' + encodeURIComponent(state.action));
    if (state.range !== 'all') params.push('since_days=' + encodeURIComponent(state.range));
    fetch('/api/backstage/forum.php?' + params.join('&'))
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (j) {
        var newRows = (j && j.data) || [];
        state.rows = state.rows.concat(newRows);
        state.exhausted = newRows.length < PAGE;
        state.offset += newRows.length;
        render();
      })
      .catch(function (err) { renderError(err); });
  }

  function skeleton() {
    if (state.offset === 0) {
      els.tbody.innerHTML = '';
      for (var i = 0; i < 5; i++) {
        els.tbody.innerHTML += '<tr class="data-explorer__skeleton">' +
          '<td><span class="skeleton--text" style="width:50%"></span></td>' +
          '<td><span class="skeleton--text" style="width:40%"></span></td>' +
          '<td><span class="skeleton--pill"></span></td>' +
          '<td><span class="skeleton--text" style="width:60%"></span></td>' +
          '<td><span class="skeleton--text" style="width:50%"></span></td></tr>';
      }
      if (els.empty) els.empty.style.display = 'none';
      if (els.error) els.error.style.display = 'none';
    }
    if (els.more) els.more.style.display = 'none';
  }

  function filtered() {
    var q = state.q.toLowerCase();
    if (!q) return state.rows;
    return state.rows.filter(function (r) { return ((r.actor_name || r.performed_by || '')).toLowerCase().indexOf(q) !== -1; });
  }

  function render() {
    var rows = filtered();
    if (!rows.length) {
      els.tbody.innerHTML = '';
      if (els.empty) els.empty.style.display = '';
      return;
    }
    if (els.empty) els.empty.style.display = 'none';
    els.tbody.innerHTML = rows.map(function (r) {
      return '<tr class="row">' +
             '<td>' + esc((r.created_at || '').slice(0,16).replace('T',' ')) + '</td>' +
             '<td>' + esc(r.actor_name || r.performed_by || '') + '</td>' +
             '<td><span class="pill pill--archived">' + esc(r.action) + '</span></td>' +
             '<td>' + esc(r.target_type) + ' ' + esc((r.target_id || '').slice(0,8)) + '</td>' +
             '<td><em style="color:var(--text-muted);">' + esc(r.reason || '') + '</em></td></tr>';
    }).join('');
    if (els.more) els.more.style.display = state.exhausted ? 'none' : '';
  }

  function renderError(err) {
    els.tbody.innerHTML = '';
    if (els.error) {
      els.error.style.display = '';
      els.error.innerHTML =
        '<div class="error-state" role="alert">' +
        '<svg class="error-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 9v4M12 17h.01M3.6 18l8.4-14 8.4 14H3.6z"/></svg>' +
        '<div><div class="error-state__title">Could not load audit log</div>' +
        '<div class="error-state__message">' + esc(err && err.message || 'Network error') + '</div></div>' +
        '<div class="error-state__actions"><button class="btn btn--primary" id="fa-retry">Retry</button></div></div>';
      var b = document.getElementById('fa-retry'); if (b) b.addEventListener('click', function () { load(true); });
    }
  }

  els.actionFilter.addEventListener('change', function () { state.action = els.actionFilter.value; load(true); });
  els.rangeFilter.addEventListener('change',  function () { state.range  = els.rangeFilter.value;  load(true); });
  els.search.addEventListener('input', function () { state.q = els.search.value || ''; render(); });
  els.more.addEventListener('click', function () { load(false); });

  function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  load(true);
})();
