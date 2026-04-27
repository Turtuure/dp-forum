<?php
$raw        = ApiClient::get('/forum/categories') ?? [];
$categories = array_map(static fn(array $c) => [
    'icon'   => $c['icon'],
    'name'   => $c['name'],
    'slug'   => $c['slug'],
    'desc'   => $c['description'],
    'topics' => $c['topic_count'],
    'posts'  => $c['post_count'],
], $raw);
?>
<section class="forum-categories">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
            <div class="col-md-6 col-lg-4">
                <a href="/forums/<?= htmlspecialchars($cat['slug']) ?>" class="forum-category-card">
                    <div class="forum-category-icon">
                        <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                    </div>
                    <div class="forum-category-name"><?= htmlspecialchars($cat['name']) ?></div>
                    <p class="forum-category-desc"><?= htmlspecialchars($cat['desc']) ?></p>
                    <div class="forum-category-meta">
                        <span><i class="bi bi-file-text"></i> <?= $cat['topics'] ?> topics</span>
                        <span><i class="bi bi-chat"></i> <?= $cat['posts'] ?> posts</span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
