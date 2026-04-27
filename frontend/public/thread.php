<?php
$topicSlug = $m[2] ?? '';
$data      = ApiClient::get('/forum/topics/' . $topicSlug);

if ($data === null) {
    http_response_code(404);
    include DAEMS_SITE_PUBLIC . '/pages/errors/404.php';
    exit;
}

$category = [
    'name' => $data['category']['name'],
    'slug' => $data['category']['slug'],
];

$t = $data['topic'];
$thread = [
    'id'      => $t['id'] ?? '',
    'title'   => $t['title'],
    'tags'    => $t['pinned'] ? ['pinned'] : [],
    'locked'  => !empty($t['locked']),
    'replies' => $t['reply_count'],
    'views'   => $t['view_count'],
    'created' => date('M j, Y', strtotime($t['created_at'])),
];

$posts = array_map(static fn(array $p) => [
    'id'              => $p['id'],
    'avatar_initials' => $p['avatar_initials'],
    'avatar_color'    => $p['avatar_color'] ?? '',
    'author'          => $p['author_name'],
    'role'            => $p['role'],
    'role_class'      => $p['role_class'],
    'joined'          => $p['joined_text'],
    'timestamp'       => date('F j, Y, H:i', strtotime($p['created_at'])),
    'likes'           => $p['likes'],
    'content'         => '<p>' . implode('</p><p>', array_map('htmlspecialchars', explode("\n\n", $p['content']))) . '</p>',
], $data['posts']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?= htmlspecialchars($thread['title']) ?> — Forums — Daem Society</title>

        <link rel="shortcut icon" href="/assets/img/brand/daems-favicon.svg" />

        <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/assets/css/bootstrap-icons.min.css" />
        <link rel="stylesheet" href="/assets/css/daems.css" />
        <link rel="stylesheet" href="/assets/css/daems-search.css" />
    </head>
    <body>

        <?php include DAEMS_SITE_PUBLIC . '/partials/top-nav.php'; ?>

        <main class="forum-subpage">
            <div class="container">

                <!-- Breadcrumb -->
                <nav class="forum-breadcrumb" aria-label="Breadcrumb">
                    <a href="/forums">Forums</a>
                    <span class="forum-breadcrumb-sep bi bi-chevron-right"></span>
                    <a href="/forums/<?= htmlspecialchars($category['slug']) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </a>
                    <span class="forum-breadcrumb-sep bi bi-chevron-right"></span>
                    <span class="forum-breadcrumb-title">
                        <?= htmlspecialchars($thread['title']) ?>
                    </span>
                </nav>

                <!-- Thread title bar -->
                <div class="forum-thread-title-area">
                    <h1><?= htmlspecialchars($thread['title']) ?></h1>
                    <div class="forum-thread-stats">
                        <span class="forum-thread-stat-item">
                            <i class="bi bi-chat"></i>
                            <?= $thread['replies'] ?> replies
                        </span>
                        <span class="forum-thread-stat-item">
                            <i class="bi bi-eye"></i>
                            <?= $thread['views'] ?> views
                        </span>
                        <span class="forum-thread-stat-item">
                            <i class="bi bi-calendar3"></i>
                            <?= htmlspecialchars($thread['created']) ?>
                        </span>
                        <a href="#forum-reply" class="btn btn-dark btn-sm ms-auto px-4">
                            <i class="bi bi-reply me-1"></i> Reply
                        </a>
                        <?php if (!empty($_SESSION['user'])): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm forum-report-btn"
                                data-target-type="topic" data-target-id="<?= htmlspecialchars($thread['id']) ?>">
                            <i class="bi bi-flag me-1"></i> Raportoi aihe
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($thread['locked']): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-lock-fill me-1"></i>
                    Keskustelu on lukittu — uusia viestejä ei voi lähettää.
                </div>
                <?php endif; ?>

                <!-- Posts -->
                <div class="forum-posts" role="list" id="forum-posts-list">

                    <?php foreach ($posts as $i => $post): ?>
                    <article class="forum-post" role="listitem" id="post-<?= $i + 1 ?>" data-post-id="<?= htmlspecialchars($post['id']) ?>"  >

                        <!-- Sidebar: avatar + author info -->
                        <div class="forum-post-sidebar">
                            <div class="forum-post-avatar"<?= $post['avatar_color'] ? ' style="background:' . htmlspecialchars($post['avatar_color']) . '"' : '' ?> aria-hidden="true">
                                <?= htmlspecialchars($post['avatar_initials']) ?>
                            </div>
                            <span class="forum-post-author-name"><?= htmlspecialchars($post['author']) ?></span>
                            <span class="forum-post-role-badge forum-post-role-badge--<?= htmlspecialchars($post['role_class']) ?>">
                                <?= htmlspecialchars($post['role']) ?>
                            </span>
                            <span class="forum-post-join-date">
                                since <?= htmlspecialchars($post['joined']) ?>
                            </span>
                        </div>

                        <!-- Post card -->
                        <div class="forum-post-body">
                            <div class="forum-post-card">

                                <!-- Post number anchor -->
                                <a href="#post-<?= $i + 1 ?>" class="forum-post-num" aria-label="Post <?= $i + 1 ?>">#<?= $i + 1 ?></a>

                                <!-- Content -->
                                <div class="forum-post-content">
                                    <?= $post['content'] ?>
                                </div>

                                <!-- Footer: timestamp + actions -->
                                <div class="forum-post-footer">
                                    <span class="forum-post-timestamp">
                                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars($post['timestamp']) ?>
                                    </span>
                                    <div class="forum-post-actions">
                                        <button
                                            type="button"
                                            class="forum-action-btn forum-like-btn"
                                            data-post-id="<?= htmlspecialchars($post['id']) ?>"
                                            aria-label="Like this post"
                                        >
                                            <i class="bi bi-heart"></i>
                                            <span class="forum-like-count"><?= $post['likes'] ?></span>
                                        </button>
                                        <button
                                            type="button"
                                            class="forum-action-btn forum-quote-btn"
                                            aria-label="Quote this post"
                                        >
                                            <i class="bi bi-quote"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="forum-action-btn forum-reply-quote-btn"
                                            aria-label="Reply to this post"
                                        >
                                            <i class="bi bi-reply"></i> Reply
                                        </button>
                                        <?php if (!empty($_SESSION['user'])): ?>
                                        <button
                                            type="button"
                                            class="forum-action-btn forum-report-btn"
                                            data-target-type="post"
                                            data-target-id="<?= htmlspecialchars($post['id']) ?>"
                                            aria-label="Raportoi tämä viesti"
                                            title="Raportoi viesti"
                                        >
                                            <i class="bi bi-flag"></i> Raportoi
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div><!-- /forum-post-card -->
                        </div><!-- /forum-post-body -->

                    </article>
                    <?php endforeach; ?>

                </div><!-- /forum-posts -->

                <!-- Reply form -->
                <div class="forum-reply-section" id="forum-reply" data-topic-slug="<?= htmlspecialchars($t['slug']) ?>">
                    <h3>Post a reply</h3>

                    <?php if (!empty($_SESSION['user']) && !isViewAsGuest()): ?>
                    <div class="forum-reply-error d-none" id="reply-error" role="alert"></div>
                    <div class="forum-reply-card">
                        <div class="mb-3">
                            <label for="reply-body" class="form-label">Your reply</label>
                            <textarea
                                id="reply-body"
                                class="form-control"
                                rows="5"
                                placeholder="<?= $thread['locked'] ? 'Topic on lukittu — uusia vastauksia ei voi lähettää.' : 'Share your thoughts…' ?>"
                                aria-label="Reply content"
                                <?= $thread['locked'] ? 'disabled' : '' ?>
                            ></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <p class="forum-reply-hint mb-0">
                                Be kind and constructive — read the
                                <a href="/terms-of-service">Community Guidelines</a>
                                before posting.
                            </p>
                            <button type="button" class="btn btn-dark px-4" id="reply-submit-btn" <?= $thread['locked'] ? 'disabled' : '' ?>>
                                <i class="bi bi-send me-1"></i> Post Reply
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="forum-reply-card text-center py-4">
                        <p class="mb-3 text-muted" style="font-size:0.95rem;">
                            You must be signed in to reply to this topic.
                        </p>
                        <a href="/join" class="btn btn-dark me-2">Join Daem Society</a>
                        <a href="/?signin=1" class="btn btn-outline-secondary">Sign In</a>
                    </div>
                    <?php endif; ?>

                </div><!-- /forum-reply-section -->

            </div><!-- /container -->
        </main>

        <?php include DAEMS_SITE_PUBLIC . '/partials/footer.php'; ?>

        <!-- Report dialog -->
        <div class="modal fade" id="forum-report-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="forum-report-title">Raportoi sisältö</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sulje"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 d-none" id="forum-report-repeat" role="status">
                            <i class="bi bi-info-circle me-1"></i>
                            Olet jo raportoinut tämän. Voit päivittää syytä — uusi raportti korvaa edellisen.
                        </div>
                        <div class="mb-3">
                            <label for="forum-report-reason" class="form-label">Syy</label>
                            <select id="forum-report-reason" class="form-select">
                                <option value="spam">Roskaposti</option>
                                <option value="harassment">Häirintä</option>
                                <option value="hate_speech">Vihapuhe</option>
                                <option value="off_topic">Aiheen vierestä</option>
                                <option value="misinformation">Virheellistä tietoa</option>
                                <option value="other">Muu</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="forum-report-detail" class="form-label">Lisätiedot (valinnainen)</label>
                            <textarea id="forum-report-detail" class="form-control" rows="3" maxlength="500"
                                      placeholder="Lisätiedot moderaattoreille&hellip;"></textarea>
                        </div>
                        <div class="forum-report-status text-muted small"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Peruuta</button>
                        <button type="button" class="btn btn-dark" id="forum-report-submit">Lähetä</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="/assets/js/bootstrap.bundle.min.js"></script>
        <script src="/assets/js/daems.js"></script>
        <script src="/assets/js/daems-search.js"></script>
        <script>daems.forumTrackView(<?= json_encode($t['slug']) ?>);</script>
        <script>
        (function () {
            var modalEl = document.getElementById('forum-report-modal');
            if (!modalEl) return;
            var modal = new bootstrap.Modal(modalEl);
            var current = { type: '', id: '' };
            var statusEl    = modalEl.querySelector('.forum-report-status');
            var repeatEl    = document.getElementById('forum-report-repeat');
            var titleEl     = document.getElementById('forum-report-title');
            var submitBtn   = document.getElementById('forum-report-submit');
            var reasonEl    = document.getElementById('forum-report-reason');
            var detailEl    = document.getElementById('forum-report-detail');
            var STORAGE_KEY = 'daems_forum_reported_v1';

            function loadReported() {
                try {
                    var raw = localStorage.getItem(STORAGE_KEY);
                    var arr = raw ? JSON.parse(raw) : [];
                    return Array.isArray(arr) ? arr : [];
                } catch (e) { return []; }
            }
            function saveReported(list) {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)); } catch (e) {}
            }
            function markReported(key) {
                var list = loadReported();
                if (list.indexOf(key) === -1) {
                    list.push(key);
                    if (list.length > 200) { list = list.slice(-200); }
                    saveReported(list);
                }
            }
            function isReported(key) { return loadReported().indexOf(key) !== -1; }
            function currentKey() { return current.type + ':' + current.id; }

            function applyReportedState() {
                var already = isReported(currentKey());
                repeatEl.classList.toggle('d-none', !already);
                submitBtn.textContent = already ? 'Päivitä syy' : 'Lähetä';
                titleEl.textContent   = already ? 'Päivitä raportti' : 'Raportoi sisältö';

                // Reflect state on the trigger button too
                document.querySelectorAll('.forum-report-btn').forEach(function (b) {
                    var k = b.getAttribute('data-target-type') + ':' + b.getAttribute('data-target-id');
                    if (isReported(k)) { b.classList.add('is-reported'); b.setAttribute('title', 'Jo raportoitu — klikkaa päivittääksesi'); }
                });
            }

            // Initial page-load pass: mark already-reported triggers visibly
            (function initTriggers() {
                document.querySelectorAll('.forum-report-btn').forEach(function (b) {
                    var k = b.getAttribute('data-target-type') + ':' + b.getAttribute('data-target-id');
                    if (isReported(k)) { b.classList.add('is-reported'); b.setAttribute('title', 'Jo raportoitu — klikkaa päivittääksesi'); }
                });
            })();

            document.querySelectorAll('.forum-report-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    current.type = btn.getAttribute('data-target-type');
                    current.id   = btn.getAttribute('data-target-id');
                    statusEl.textContent = '';
                    reasonEl.value = 'spam';
                    detailEl.value = '';
                    applyReportedState();
                    modal.show();
                });
            });

            submitBtn.addEventListener('click', function () {
                var reason = reasonEl.value;
                var detail = detailEl.value;
                statusEl.textContent = 'Lähetetään…';
                submitBtn.disabled = true;
                fetch('/api/forum/report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        target_type: current.type,
                        target_id: current.id,
                        reason_category: reason,
                        reason_detail: detail || null,
                    }),
                }).then(function (r) {
                    return r.json().then(function (b) { return { status: r.status, body: b }; });
                }).then(function (res) {
                    submitBtn.disabled = false;
                    if (res.status >= 200 && res.status < 300) {
                        markReported(currentKey());
                        applyReportedState();
                        statusEl.textContent = 'Kiitos raportista. Moderaattori tarkistaa.';
                        setTimeout(function () { modal.hide(); }, 1200);
                    } else {
                        statusEl.textContent = 'Virhe: ' + ((res.body && res.body.error) || res.status);
                    }
                }).catch(function () {
                    submitBtn.disabled = false;
                    statusEl.textContent = 'Verkkovirhe.';
                });
            });
        })();
        </script>
        <style>
            .forum-report-btn.is-reported { color: #b45309; }
            .forum-report-btn.is-reported .bi-flag::before { content: "\F40D"; /* bi-flag-fill */ }
        </style>
    </body>
</html>
