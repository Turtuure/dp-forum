<?php
function forumRelativeTime(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)   return max(1, (int) ($diff / 60)) . 'm';
    if ($diff < 86400)  return (int) ($diff / 3600) . 'h';
    if ($diff < 604800) return (int) ($diff / 86400) . 'd';
    return (int) ($diff / 604800) . 'w';
}

function renderAvatarXs(array $a): string {
    $color = $a['color'] ?? '';
    $cls   = 'forum-avatar-xs' . ($color ? ' forum-avatar-xs--custom' : '');
    $style = $color ? ' style="background:' . htmlspecialchars($color) . '"' : '';
    return '<span class="' . $cls . '"' . $style . ' aria-hidden="true">'
         . htmlspecialchars($a['initials']) . '</span>';
}

$categorySlug = $m[1] ?? '';
$data         = ApiClient::get('/forum/categories/' . $categorySlug);

if ($data === null) {
    http_response_code(404);
    include DAEMS_SITE_PUBLIC . '/pages/errors/404.php';
    exit;
}

$cat    = $data['category'];
$rawTopics = $data['topics'] ?? [];

$category = [
    'name' => $cat['name'],
    'slug' => $cat['slug'],
    'desc' => $cat['description'],
    'icon' => $cat['icon'],
];

$topics = array_map(static fn(array $t) => [
    'slug'        => $t['slug'],
    'title'       => $t['title'],
    'tags'        => $t['pinned'] ? ['pinned'] : [],
    'avatars'     => [['initials' => $t['avatar_initials'], 'color' => $t['avatar_color'] ?? '']],
    'by'          => $t['author_name'],
    'replies'     => $t['reply_count'],
    'views'       => $t['view_count'],
    'activity'    => forumRelativeTime($t['last_activity_at']),
    'activity_by' => $t['last_activity_by'],
], $rawTopics);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?= htmlspecialchars($category['name']) ?> — Forums — Daem Society</title>

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
                    <span><?= htmlspecialchars($category['name']) ?></span>
                </nav>

                <!-- Category header -->
                <div class="forum-cat-header">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div>
                            <h1>
                                <i class="bi <?= htmlspecialchars($category['icon']) ?> text-primary me-2 fs-4"></i><?= htmlspecialchars($category['name']) ?>
                            </h1>
                            <p class="forum-cat-header-desc"><?= htmlspecialchars($category['desc']) ?></p>
                        </div>
                        <?php if (!empty($_SESSION['user']) && !isViewAsGuest()): ?>
                        <a href="/forums/<?= htmlspecialchars($category['slug']) ?>/new" class="btn btn-dark btn-sm px-4 flex-shrink-0" aria-label="Start a new topic">
                            <i class="bi bi-plus-lg me-1"></i> New Topic
                        </a>
                        <?php else: ?>
                        <a href="/?signin=1" class="btn btn-outline-secondary btn-sm px-4 flex-shrink-0">
                            <i class="bi bi-lock me-1"></i> Sign in to post
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Topic toolbar: tabs + search -->
                <div class="forum-topic-toolbar">
                    <div class="forum-tab-group" role="tablist">
                        <a href="?sort=latest" class="forum-tab-btn active" role="tab" aria-selected="true">
                            <i class="bi bi-clock"></i> Latest
                        </a>
                        <a href="?sort=top" class="forum-tab-btn" role="tab" aria-selected="false">
                            <i class="bi bi-fire"></i> Top
                        </a>
                        <a href="?sort=new" class="forum-tab-btn" role="tab" aria-selected="false">
                            <i class="bi bi-stars"></i> New
                        </a>
                    </div>

                    <div class="input-group input-group-sm forum-topic-search">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input
                            type="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Search topics…"
                            aria-label="Search topics"
                        />
                    </div>
                </div>

                <!-- Topic table -->
                <div class="forum-topic-table" role="list">

                    <!-- Table header (hidden on mobile) -->
                    <div class="forum-topic-table-head" aria-hidden="true">
                        <div class="forum-topic-table-head-col">Topic</div>
                        <div class="forum-topic-table-head-col">Replies</div>
                        <div class="forum-topic-table-head-col">Views</div>
                        <div class="forum-topic-table-head-col">Activity</div>
                    </div>

                    <?php foreach ($topics as $i => $topic): ?>
                    <a
                        href="/forums/<?= htmlspecialchars($category['slug']) ?>/<?= htmlspecialchars($topic['slug']) ?>"
                        class="forum-topic-row"
                        role="listitem"
                    >
                        <!-- Title column -->
                        <div class="forum-topic-col-title">
                            <div class="forum-topic-title-row">
                                <span class="forum-topic-title-text"><?= htmlspecialchars($topic['title']) ?></span>
                                <?php foreach ($topic['tags'] as $tag): ?>
                                    <?php if ($tag === 'pinned'): ?>
                                    <span class="forum-topic-tag forum-topic-tag--pinned">
                                        <i class="bi bi-pin-angle"></i> Pinned
                                    </span>
                                    <?php elseif ($tag === 'announcement'): ?>
                                    <span class="forum-topic-tag forum-topic-tag--announcement">
                                        <i class="bi bi-megaphone"></i> Announcement
                                    </span>
                                    <?php else: ?>
                                    <span class="forum-topic-tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="forum-topic-meta-row">
                                <div class="forum-topic-avatars" aria-hidden="true">
                                    <?php foreach (array_slice($topic['avatars'], 0, 4) as $a): ?>
                                        <?= renderAvatarXs($a) ?>
                                    <?php endforeach; ?>
                                </div>
                                <span class="forum-topic-byline">
                                    by <?= htmlspecialchars($topic['by']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Replies -->
                        <div class="forum-topic-col-replies">
                            <span class="forum-topic-col-count-num"><?= $topic['replies'] ?></span>
                            <span class="forum-topic-col-count-lbl">Replies</span>
                        </div>

                        <!-- Views -->
                        <div class="forum-topic-col-views">
                            <span class="forum-topic-col-count-num"><?= $topic['views'] ?></span>
                            <span class="forum-topic-col-count-lbl">Views</span>
                        </div>

                        <!-- Activity -->
                        <div class="forum-topic-col-activity">
                            <span class="forum-topic-activity-time"><?= htmlspecialchars($topic['activity']) ?></span>
                            <span class="forum-topic-activity-user"><?= htmlspecialchars($topic['activity_by']) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>

                </div><!-- /forum-topic-table -->

            </div><!-- /container -->
        </main>

        <?php include DAEMS_SITE_PUBLIC . '/partials/footer.php'; ?>

        <script src="/assets/js/bootstrap.bundle.min.js"></script>
        <script src="/assets/js/daems.js"></script>
        <script src="/assets/js/daems-search.js"></script>
    </body>
</html>
