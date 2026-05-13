<?php
// One-off backfill — copies the first post's body into the topic's
// first_post_search_text column. Idempotent (NULL filter), safe to re-run.
//
// 2026-05-13: Made harness-friendly. The integration test runner and CI's
// apply_pending_migrations.php both pass their own $pdo via require scope;
// the hardcoded fallback only runs when invoked directly on a dev shell.
declare(strict_types=1);

/** @var \PDO $pdo MigrationTestCase + apply_pending_migrations pass theirs. */
if (!isset($pdo) || !$pdo instanceof PDO) {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=daems_db;charset=utf8mb4', 'root', 'salasana', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}
$sql = 'UPDATE forum_topics ft
           SET first_post_search_text = (
             SELECT content FROM forum_posts p
              WHERE p.topic_id = ft.id
              ORDER BY p.sort_order ASC, p.created_at ASC
              LIMIT 1
           )
         WHERE ft.first_post_search_text IS NULL';
$affected = $pdo->exec($sql);
echo "Backfilled {$affected} topics\n";
