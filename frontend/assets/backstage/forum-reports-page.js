/**
 * Forum reports admin page — list + slide-panel resolve flow + confirm dismiss.
 * Uses window.SlidePanel + window.ConfirmDialog from daems-backstage-system.js.
 *
 * Endpoints (via daem-society proxy):
 *   GET  /api/backstage/forum.php?op=reports_list&status=...&target_type=...
 *   GET  /api/backstage/forum.php?op=report_detail&id=...
 *   POST /api/backstage/forum.php?op=report_resolve&id=...   body: {action, reason, ...}
 *   POST /api/backstage/forum.php?op=report_dismiss&id=...
 */
(function () {
  'use strict';

  var els = {
    list:    document.getElementById('fr-list-mount'),
    empty:   document.getElementById('fr-empty-mount'),
    error:   document.getElementById('fr-error-mount'),
    seg:     document.getElementById('fr-status-filter'),
    target:  document.getElementById('fr-target-filter'),
    search:  document.getElementById('fr-search'),
  };
  if (!els.list) return;

  var state = { status: 'open', target: '', q: '', rows: [] };

  function load() {
    setSkeleton();
    var qs = '?op=reports_list&status=' + encodeURIComponent(state.status) +
             (state.target ? '&target_type=' + encodeURIComponent(state.target) : '');
    fetch('/api/backstage/forum.php' + qs)
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (j) {
        state.rows = (j && j.data) || [];
        render();
      })
      .catch(function (err) { renderError(err); });
  }

  function setSkeleton() {
    els.list.innerHTML = '';
    for (var i = 0; i < 3; i++) {
      els.list.innerHTML += '<div class="data-explorer__skeleton" style="height: 100px; margin: 8px 0; background: var(--surface-light); border-radius: 8px;"></div>';
    }
    if (els.empty) els.empty.style.display = 'none';
    if (els.error) els.error.style.display = 'none';
  }

  function filtered() {
    var q = state.q.toLowerCase();
    if (!q) return state.rows;
    return state.rows.filter(function (r) {
      return ((r.target_excerpt || '') + (r.target_type || '')).toLowerCase().indexOf(q) !== -1;
    });
  }

  function render() {
    var rows = filtered();
    if (!rows.length) {
      els.list.innerHTML = '';
      if (els.empty) els.empty.style.display = '';
      return;
    }
    if (els.empty) els.empty.style.display = 'none';

    els.list.innerHTML = rows.map(reportCardHtml).join('');
    Array.from(els.list.querySelectorAll('[data-act="resolve"]')).forEach(function (b) {
      b.addEventListener('click', function () { openResolvePanel(b.getAttribute('data-id')); });
    });
    Array.from(els.list.querySelectorAll('[data-act="dismiss"]')).forEach(function (b) {
      b.addEventListener('click', function () { dismissReport(b.getAttribute('data-id')); });
    });
  }

  function reportCardHtml(r) {
    var cid = r.compound_id || r.id || '';
    var typeLabel = r.target_type === 'topic' ? 'Topic' : 'Post';
    var statusPill = '<span class="pill pill--' + (r.status === 'open' ? 'pending' : 'archived') + '">' + esc(r.status) + '</span>';
    var reasonsHtml = (r.reason_counts ? Object.keys(r.reason_counts).map(function (k) {
      return '<span class="report-reason-chip">' + esc(k) + ' \xd7' + r.reason_counts[k] + '</span>';
    }).join(' ') : '');
    return '<article class="card" style="margin-bottom: 10px; padding: 14px 16px;">' +
           '  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">' +
           '    <div>' +
           '      <span class="pill pill--scheduled">' + typeLabel + '</span> ' +
           '      <strong>' + (r.report_count || 1) + ' report' + ((r.report_count || 1) === 1 ? '' : 's') + '</strong> \xb7 ' +
           '      ' + statusPill +
           '      <p style="margin:8px 0; color: var(--text-secondary); font-size: 13px;">' + esc((r.target_excerpt || '').slice(0, 240)) + '</p>' +
           '      <div style="margin-top: 6px;">' + reasonsHtml + '</div>' +
           '    </div>' +
           '    <div style="display:flex;gap:6px;">' +
           '      <button class="btn btn--secondary" data-act="resolve" data-id="' + esc(cid) + '">Resolve…</button>' +
           '      <button class="btn btn--text"      data-act="dismiss" data-id="' + esc(cid) + '">Dismiss</button>' +
           '    </div>' +
           '  </div>' +
           '</article>';
  }

  function renderError(err) {
    els.list.innerHTML = '';
    if (els.error) {
      els.error.style.display = '';
      els.error.innerHTML =
        '<div class="error-state" role="alert">' +
        '  <svg class="error-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 9v4M12 17h.01M3.6 18l8.4-14 8.4 14H3.6z"/></svg>' +
        '  <div><div class="error-state__title">Could not load reports</div>' +
        '  <div class="error-state__message">' + esc(err && err.message || 'Network error') + '</div></div>' +
        '  <div class="error-state__actions"><button class="btn btn--primary" id="fr-retry">Retry</button></div>' +
        '</div>';
      var b = document.getElementById('fr-retry');
      if (b) b.addEventListener('click', load);
    }
  }

  function openResolvePanel(id) {
    fetch('/api/backstage/forum.php?op=report_detail&id=' + encodeURIComponent(id))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        showPanel(id, normalizeDetail((j && j.data) || {}));
      });
  }

  /**
   * API returns `{aggregated, raw_reports, target_content}` where target_content
   * is an object `{author, title|content, created_at}`. Flatten + stringify so
   * the panel template can keep using `detail.target_type`, `detail.target_text`,
   * `detail.reporter_rows`.
   */
  function normalizeDetail(d) {
    var agg = d.aggregated || {};
    var tc = d.target_content || {};
    var contentText = '';
    if (typeof tc === 'string') {
      contentText = tc;
    } else if (tc && (tc.title || tc.content)) {
      contentText = (tc.title || tc.content || '').toString();
      if (tc.author) contentText = tc.author + ' — ' + contentText;
    }
    return {
      target_type:    agg.target_type || '',
      target_id:      agg.target_id || '',
      target_text:    contentText || '(content unavailable)',
      // Raw editable body — title for topics, content for posts. Used by the
      // "Edit content" action form's textarea prefill.
      raw_content:    (tc && (tc.content || tc.title)) || '',
      reporter_rows:  (d.raw_reports || []).map(function (r) {
        return {
          reporter_id:    r.reporter_user_id || '',
          reporter_name:  r.reporter_name || '',
          reason:         r.reason_category || '',
          comment:        r.reason_detail || '',
        };
      }),
    };
  }

  function showPanel(id, detail) {
    var body = document.createElement('div');
    body.innerHTML =
      '<h3 style="font-size:14px;margin:0 0 6px;">Reported ' + esc(detail.target_type || '') + '</h3>' +
      '<p style="background:var(--surface-light); padding:10px; border-radius:6px; font-size:13px;">' +
      esc(detail.target_text) + '</p>' +
      '<h3 style="font-size:14px;margin:14px 0 6px;">Reporters (' + (detail.reporter_rows ? detail.reporter_rows.length : 0) + ')</h3>' +
      '<ul style="list-style:none;padding:0;margin:0;">' +
      (detail.reporter_rows ? detail.reporter_rows.map(function (rr) {
        return '<li style="padding:6px 0;border-bottom:1px solid var(--surface-border);font-size:13px;">' +
               esc(rr.reporter_name || rr.reporter_id || 'unknown') +
               ' — <strong>' + esc(rr.reason) + '</strong>' +
               (rr.comment ? ' \xb7 <em style="color:var(--text-muted);">' + esc(rr.comment) + '</em>' : '') + '</li>';
      }).join('') : '') +
      '</ul>' +
      '<h3 style="font-size:14px;margin:14px 0 6px;">Resolve with</h3>' +
      '<div id="fr-actions" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">' +
      '  <button type="button" class="btn btn--danger"    data-resolve="deleted">Delete content</button>' +
      (detail.target_type === 'topic'
        ? '<button type="button" class="btn btn--secondary" data-resolve="locked">Lock topic</button>' : '') +
      '  <button type="button" class="btn btn--secondary" data-resolve="warned">Warn user</button>' +
      '  <button type="button" class="btn btn--secondary" data-resolve="edited">Edit content</button>' +
      '  <button type="button" class="btn btn--text"      data-resolve="dismissed">Dismiss without action</button>' +
      '</div>' +
      '<div id="fr-action-form" style="margin-top:10px;"></div>';

    var footer = document.createElement('div');
    footer.style.display = 'contents';
    var cancel = document.createElement('button');
    cancel.type = 'button'; cancel.className = 'btn btn--secondary'; cancel.textContent = 'Cancel';
    cancel.addEventListener('click', function () { window.SlidePanel.close(); });
    footer.appendChild(cancel);

    window.SlidePanel.open({ title: 'Resolve report', body: body, footer: footer });

    Array.from(body.querySelectorAll('[data-resolve]')).forEach(function (b) {
      b.addEventListener('click', function () {
        showActionForm(id, b.getAttribute('data-resolve'), detail);
      });
    });
  }

  function showActionForm(id, action, detail) {
    var formMount = document.getElementById('fr-action-form');
    if (!formMount) return;

    if (action === 'edited') {
      formMount.innerHTML =
        '<label style="display:block;font-size:11px;text-transform:uppercase;color:var(--text-secondary);margin-bottom:4px;">Edit content</label>' +
        '<textarea id="fr-form-content" rows="6" class="data-explorer__search" style="width:100%;font-family:inherit;">' +
        esc(detail.raw_content || '') + '</textarea>' +
        '<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">' +
        '  <button type="button" class="btn btn--primary" id="fr-form-apply">Apply edit</button></div>';
    } else if (action === 'warned') {
      formMount.innerHTML =
        '<label style="display:block;font-size:11px;text-transform:uppercase;color:var(--text-secondary);margin-bottom:4px;">Reason for warning</label>' +
        '<textarea id="fr-form-reason" rows="3" class="data-explorer__search" style="width:100%;font-family:inherit;"></textarea>' +
        '<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">' +
        '  <button type="button" class="btn btn--primary" id="fr-form-apply">Send warning</button></div>';
    } else {
      // deleted / locked / dismissed: just confirm
      var verb = action === 'dismissed' ? 'Dismiss without action' : (action.charAt(0).toUpperCase() + action.slice(1));
      formMount.innerHTML =
        '<p style="color:var(--text-secondary);font-size:13px;margin:0;">Click apply to ' + verb.toLowerCase() + '.</p>' +
        '<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">' +
        '  <button type="button" class="btn btn--' + (action === 'deleted' ? 'danger' : 'primary') + '" id="fr-form-apply">' + verb + '</button></div>';
    }

    var apply = document.getElementById('fr-form-apply');
    if (apply) apply.addEventListener('click', function () {
      var payload = { action: action };
      if (action === 'edited')  payload.content = document.getElementById('fr-form-content').value;
      if (action === 'warned')  payload.reason  = document.getElementById('fr-form-reason').value;
      submitResolve(id, payload, action === 'deleted');
    });
  }

  function submitResolve(id, payload, danger) {
    var op = payload.action === 'dismissed' ? 'report_dismiss' : 'report_resolve';
    window.ConfirmDialog.open({
      title: 'Apply ' + payload.action + '?',
      body:  payload.action === 'deleted' ? 'This action cannot be undone.' : 'Mark this report as ' + payload.action + '.',
      danger: !!danger,
      confirmLabel: danger ? 'Delete' : 'Apply',
    }).then(function (ok) {
      if (!ok) return;
      fetch('/api/backstage/forum.php?op=' + op + '&id=' + encodeURIComponent(id), {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload),
      }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        window.SlidePanel.close();
        load();
      }).catch(function (e) { alert('Action failed: ' + e.message); });
    });
  }

  function dismissReport(id) {
    window.ConfirmDialog.open({
      title:  'Dismiss report?',
      body:   'The report will be marked as dismissed without further action.',
      danger: false,
      confirmLabel: 'Dismiss',
    }).then(function (ok) {
      if (!ok) return;
      fetch('/api/backstage/forum.php?op=report_dismiss&id=' + encodeURIComponent(id), { method: 'POST' })
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          load();
        }).catch(function (e) { alert('Dismiss failed: ' + e.message); });
    });
  }

  // Wiring
  els.seg.addEventListener('click', function (e) {
    var b = e.target.closest('[data-status]');
    if (!b) return;
    Array.from(els.seg.querySelectorAll('[data-status]')).forEach(function (x) { x.classList.remove('is-active'); });
    b.classList.add('is-active');
    state.status = b.getAttribute('data-status') === 'all' ? '' : b.getAttribute('data-status');
    load();
  });
  els.target.addEventListener('change', function () {
    state.target = els.target.value;
    load();
  });
  els.search.addEventListener('input', function () {
    state.q = els.search.value || '';
    render();
  });

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  load();
})();
