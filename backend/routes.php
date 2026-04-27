<?php
declare(strict_types=1);

use Daems\Infrastructure\Framework\Container\Container;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use Daems\Infrastructure\Framework\Http\Router;
use Daems\Infrastructure\Framework\Http\Middleware\AuthMiddleware;
use Daems\Infrastructure\Framework\Http\Middleware\TenantContextMiddleware;
use DaemsModule\Forum\Controller\ForumController;
use DaemsModule\Forum\Controller\ForumModerationBackstageController;
use DaemsModule\Forum\Controller\ForumContentBackstageController;

return function (Router $router, Container $container): void {
    $tenant = [TenantContextMiddleware::class];
    $auth   = [TenantContextMiddleware::class, AuthMiddleware::class];

    // ===== Public Forum (8 routes) =====
    $router->get('/api/v1/forum/categories', static function (Request $req) use ($container): Response {
        return $container->make(ForumController::class)->index($req);
    }, $tenant);
    $router->get('/api/v1/forum/categories/{slug}', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->category($req, $p);
    }, $tenant);
    $router->get('/api/v1/forum/topics/{slug}', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->thread($req, $p);
    }, $tenant);
    $router->post('/api/v1/forum/categories/{slug}/topics', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->createTopic($req, $p);
    }, $auth);
    $router->post('/api/v1/forum/topics/{slug}/posts', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->createPost($req, $p);
    }, $auth);
    $router->post('/api/v1/forum/posts/{id}/like', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->likePost($req, $p);
    }, $auth);
    $router->post('/api/v1/forum/topics/{slug}/view', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumController::class)->incrementView($req, $p);
    }, $tenant);
    $router->post('/api/v1/forum/reports', static function (Request $req) use ($container): Response {
        return $container->make(ForumController::class)->createReport($req);
    }, $auth);

    // ===== Backstage Moderation (11 routes) → ForumModerationBackstageController =====
    $router->get('/api/v1/backstage/forum/reports', static function (Request $req) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->listForumReports($req);
    }, $auth);
    $router->get('/api/v1/backstage/forum/reports/{id}', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->getForumReport($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/reports/{id}/resolve', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->resolveForumReport($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/reports/{id}/dismiss', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->dismissForumReport($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/topics/{id}/lock', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->lockForumTopic($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/topics/{id}/unlock', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->unlockForumTopic($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/topics/{id}/delete', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->deleteForumTopicAdmin($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/posts/{id}/edit', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->editForumPostAdmin($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/posts/{id}/delete', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->deleteForumPostAdmin($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/users/{id}/warn', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->warnForumUser($req, $p);
    }, $auth);
    $router->get('/api/v1/backstage/forum/audit', static function (Request $req) use ($container): Response {
        return $container->make(ForumModerationBackstageController::class)->listForumAudit($req);
    }, $auth);

    // ===== Backstage Content (9 routes) → ForumContentBackstageController =====
    $router->get('/api/v1/backstage/forum/stats', static function (Request $req) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->statsForum($req);
    }, $auth);
    $router->get('/api/v1/backstage/forum/topics', static function (Request $req) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->listForumTopicsAdmin($req);
    }, $auth);
    $router->post('/api/v1/backstage/forum/topics/{id}/pin', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->pinForumTopic($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/topics/{id}/unpin', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->unpinForumTopic($req, $p);
    }, $auth);
    $router->get('/api/v1/backstage/forum/posts', static function (Request $req) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->listForumPostsAdmin($req);
    }, $auth);
    $router->get('/api/v1/backstage/forum/categories', static function (Request $req) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->listForumCategoriesAdmin($req);
    }, $auth);
    $router->post('/api/v1/backstage/forum/categories', static function (Request $req) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->createForumCategoryAdmin($req);
    }, $auth);
    $router->post('/api/v1/backstage/forum/categories/{id}', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->updateForumCategoryAdmin($req, $p);
    }, $auth);
    $router->post('/api/v1/backstage/forum/categories/{id}/delete', static function (Request $req, array $p) use ($container): Response {
        return $container->make(ForumContentBackstageController::class)->deleteForumCategoryAdmin($req, $p);
    }, $auth);
};
