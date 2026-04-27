<?php
if (empty($_SESSION['user'])) {
    header('Location: /?signin=1');
    exit;
}

$categorySlug = $m[1] ?? '';
$data         = ApiClient::get('/forum/categories/' . $categorySlug);

if ($data === null) {
    http_response_code(404);
    include DAEMS_SITE_PUBLIC . '/pages/errors/404.php';
    exit;
}

$cat = $data['category'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>New Topic — <?= htmlspecialchars($cat['name']) ?> — Forums — Daem Society</title>

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

                <nav class="forum-breadcrumb" aria-label="Breadcrumb">
                    <a href="/forums">Forums</a>
                    <span class="forum-breadcrumb-sep bi bi-chevron-right"></span>
                    <a href="/forums/<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a>
                    <span class="forum-breadcrumb-sep bi bi-chevron-right"></span>
                    <span>New Topic</span>
                </nav>

                <div class="forum-new-topic-wrap">
                    <div class="forum-new-topic-header">
                        <h1><i class="bi <?= htmlspecialchars($cat['icon']) ?> me-2 text-primary fs-5"></i>New Topic in <?= htmlspecialchars($cat['name']) ?></h1>
                    </div>

                    <div class="forum-new-topic-error d-none" id="new-topic-error" role="alert"></div>

                    <form id="new-topic-form" class="forum-new-topic-form" novalidate>
                        <input type="hidden" name="category_slug" value="<?= htmlspecialchars($cat['slug']) ?>" />

                        <div class="mb-3">
                            <label for="new-topic-title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="new-topic-title"
                                name="title"
                                placeholder="What is your topic about?"
                                maxlength="200"
                                required
                                autofocus
                            />
                        </div>

                        <div class="mb-4">
                            <label for="new-topic-content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control forum-new-topic-textarea"
                                id="new-topic-content"
                                name="content"
                                rows="10"
                                placeholder="Write your post here…"
                                required
                            ></textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <a href="/forums/<?= htmlspecialchars($cat['slug']) ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-dark px-5" id="new-topic-btn">
                                <i class="bi bi-send me-1"></i> Post Topic
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>

        <?php include DAEMS_SITE_PUBLIC . '/partials/footer.php'; ?>

        <script src="/assets/js/bootstrap.bundle.min.js"></script>
        <script src="/assets/js/daems.js"></script>
        <script src="/assets/js/daems-search.js"></script>
    </body>
</html>
