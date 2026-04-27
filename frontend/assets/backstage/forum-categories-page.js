/**
 * Forum categories admin — table + slide-panel CRUD + confirm delete.
 *
 * Endpoints:
 *   GET  /api/backstage/forum.php?op=categories_list
 *   POST /api/backstage/forum.php?op=category_create        body: {name, slug, icon, description, sort_order}
 *   POST /api/backstage/forum.php?op=category_update&id=... body: same payload
 *   POST /api/backstage/forum.php?op=category_delete&id=...
 */
(function () {
  'use strict';

  var els = {
    tbody:  document.getElementById('fc-tbody'),
    addBtn: document.getElementById('fc-add-btn'),
    search: document.getElementById('fc-search'),
    empty:  document.getElementById('fc-empty-mount'),
    error:  document.getElementById('fc-error-mount'),
  };
  if (!els.tbody) return;

  var state = { rows: [], q: '' };

  function load() {
    skeleton();
    fetch('/api/backstage/forum.php?op=categories_list')
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (j) { state.rows = (j && j.data) || []; render(); })
      .catch(function (err) { renderError(err); });
  }

  function skeleton() {
    els.tbody.innerHTML = '';
    for (var i = 0; i < 4; i++) {
      els.tbody.innerHTML += '<tr class="data-explorer__skeleton">' +
        '<td><span class="skeleton--text" style="width:60%"></span></td>' +
        '<td><span class="skeleton--text" style="width:40%"></span></td>' +
        '<td><span class="skeleton--text" style="width:80%"></span></td>' +
        '<td><span class="skeleton--text" style="width:30%"></span></td>' +
        '<td><span class="skeleton--text" style="width:30%"></span></td>' +
        '<td></td></tr>';
    }
    if (els.empty) els.empty.style.display = 'none';
    if (els.error) els.error.style.display = 'none';
  }

  function filtered() {
    var q = state.q.toLowerCase();
    if (!q) return state.rows;
    return state.rows.filter(function (r) {
      return ((r.name || '') + (r.slug || '')).toLowerCase().indexOf(q) !== -1;
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
    els.tbody.innerHTML = rows.map(function (r) {
      var desc = (r.description || '').slice(0, 80) + ((r.description || '').length > 80 ? '…' : '');
      return '<tr class="row" data-id="' + esc(r.id) + '">' +
             '<td><strong>' + esc(r.icon || '') + ' ' + esc(r.name || '') + '</strong></td>' +
             '<td><code style="font-size:12px;">' + esc(r.slug || '') + '</code></td>' +
             '<td>' + esc(desc) + '</td>' +
             '<td>' + (r.topic_count || 0) + '</td>' +
             '<td>' + (r.sort_order || 0) + '</td>' +
             '<td class="data-explorer__actions">' +
             '<button class="btn btn--icon" data-action="edit"   data-id="' + esc(r.id) + '" title="Edit">✎</button>' +
             '<button class="btn btn--icon" data-action="delete" data-id="' + esc(r.id) + '" data-name="' + esc(r.name || '') + '" data-count="' + (r.topic_count || 0) + '" title="Delete">🗑</button>' +
             '</td></tr>';
    }).join('');

    Array.from(els.tbody.querySelectorAll('[data-action="edit"]')).forEach(function (b) {
      b.addEventListener('click', function () {
        var row = state.rows.filter(function (r) { return r.id === b.getAttribute('data-id'); })[0];
        if (row) openPanel('edit', row);
      });
    });
    Array.from(els.tbody.querySelectorAll('[data-action="delete"]')).forEach(function (b) {
      b.addEventListener('click', function () { confirmDelete(b.getAttribute('data-id'), b.getAttribute('data-name'), parseInt(b.getAttribute('data-count') || '0', 10)); });
    });
  }

  function renderError(err) {
    els.tbody.innerHTML = '';
    if (els.error) {
      els.error.style.display = '';
      els.error.innerHTML =
        '<div class="error-state" role="alert">' +
        '<svg class="error-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 9v4M12 17h.01M3.6 18l8.4-14 8.4 14H3.6z"/></svg>' +
        '<div><div class="error-state__title">Could not load categories</div>' +
        '<div class="error-state__message">' + esc(err && err.message || 'Network error') + '</div></div>' +
        '<div class="error-state__actions"><button class="btn btn--primary" id="fc-retry">Retry</button></div></div>';
      var b = document.getElementById('fc-retry'); if (b) b.addEventListener('click', load);
    }
  }

  function openPanel(mode, row) {
    var data = row || {};
    var body = document.createElement('div');
    body.innerHTML =
      '<div><label class="kpi-card__label">Name</label><input type="text" name="name" class="data-explorer__search" style="width:100%;margin-top:6px;" value="' + escAttr(data.name) + '"></div>' +
      '<div><label class="kpi-card__label">Slug</label><input type="text" name="slug" class="data-explorer__search" style="width:100%;margin-top:6px;" value="' + escAttr(data.slug) + '"></div>' +
      '<div><label class="kpi-card__label">Icon (emoji or class name)</label><input type="text" name="icon" class="data-explorer__search" style="width:100%;margin-top:6px;" value="' + escAttr(data.icon) + '"></div>' +
      '<div><label class="kpi-card__label">Description</label><textarea name="description" rows="4" class="data-explorer__search" style="width:100%;margin-top:6px;font-family:inherit;">' + esc(data.description) + '</textarea></div>' +
      '<div><label class="kpi-card__label">Sort order</label><input type="number" name="sort_order" class="data-explorer__search" style="width:100%;margin-top:6px;" value="' + esc(data.sort_order || 0) + '"></div>';

    var footer = document.createElement('div');
    footer.style.display = 'contents';
    var cancel = document.createElement('button');
    cancel.type = 'button'; cancel.className = 'btn btn--secondary'; cancel.textContent = 'Cancel';
    cancel.addEventListener('click', function () { window.SlidePanel.close(); });
    footer.appendChild(cancel);
    var save = document.createElement('button');
    save.type = 'button'; save.className = 'btn btn--primary'; save.textContent = mode === 'create' ? 'Create' : 'Save';
    save.addEventListener('click', function () { savePanel(mode, data.id, body); });
    footer.appendChild(save);

    window.SlidePanel.open({ title: mode === 'create' ? 'New category' : 'Edit category', body: body, footer: footer });
  }

  function savePanel(mode, id, bodyEl) {
    var payload = {
      name:        bodyEl.querySelector('[name="name"]').value,
      slug:        bodyEl.querySelector('[name="slug"]').value,
      icon:        bodyEl.querySelector('[name="icon"]').value,
      description: bodyEl.querySelector('[name="description"]').value,
      sort_order:  parseInt(bodyEl.querySelector('[name="sort_order"]').value || '0', 10),
    };
    var op = mode === 'create' ? 'category_create' : ('category_update&id=' + encodeURIComponent(id));
    fetch('/api/backstage/forum.php?op=' + op, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    }).then(function (r) {
      if (!r.ok) return r.json().then(function (e) { throw new Error(e && e.error || ('HTTP ' + r.status)); });
      window.SlidePanel.close(); load();
    }).catch(function (e) { alert('Save failed: ' + e.message); });
  }

  function confirmDelete(id, name, topicCount) {
    var body = topicCount > 0
      ? '"' + (name || '') + '" has ' + topicCount + ' topic' + (topicCount === 1 ? '' : 's') + '. The backend may reject the deletion.'
      : 'Delete "' + (name || '') + '"? This cannot be undone.';
    window.ConfirmDialog.open({ title: 'Delete category?', body: body, danger: true, confirmLabel: 'Delete' })
      .then(function (ok) {
        if (!ok) return;
        fetch('/api/backstage/forum.php?op=category_delete&id=' + encodeURIComponent(id), { method: 'POST' })
          .then(function (r) {
            if (!r.ok) return r.json().then(function (e) { throw new Error(e && e.error || ('HTTP ' + r.status)); });
            load();
          })
          .catch(function (e) { alert('Delete failed: ' + e.message); });
      });
  }

  els.addBtn.addEventListener('click', function () { openPanel('create', null); });
  els.search.addEventListener('input', function () { state.q = els.search.value || ''; render(); });

  function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function escAttr(s) { return esc(s); }

  load();
})();
