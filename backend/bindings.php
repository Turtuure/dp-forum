<?php
declare(strict_types=1);

use Daems\Infrastructure\Framework\Container\Container;
use Daems\Infrastructure\Framework\Database\Connection;

/**
 * Forum module — production DI bindings.
 *
 * Loaded by ModuleRegistry::registerBindings() AFTER core bootstrap/app.php,
 * so these bindings WIN over any older Forum bindings still present there
 * (Task 21 will remove the originals).
 *
 * Architecture: Domain stays in core. The 4 repository INTERFACES live at
 * \Daems\Domain\Forum\Forum*RepositoryInterface and are bound here to the
 * module's SQL implementations under \DaemsModule\Forum\Infrastructure\.
 *
 * NOTE: ForumIdentityDeriver is NOT bound — it's a final class with public
 * static derive()/initials() methods (no instance dependencies).
 *
 * Inventory: 4 repos + 30 use cases (8 public + 22 admin) + 3 controllers = 37 bindings.
 */
return function (Container $container): void {
    // ---------------------------------------------------------------------
    // 4× Repository bindings — CORE interfaces → MODULE SQL impls
    // ---------------------------------------------------------------------
    $container->singleton(
        \Daems\Domain\Forum\ForumRepositoryInterface::class,
        static fn(Container $c) => new \DaemsModule\Forum\Infrastructure\SqlForumRepository(
            $c->make(Connection::class),
        ),
    );
    $container->singleton(
        \Daems\Domain\Forum\ForumReportRepositoryInterface::class,
        static fn(Container $c) => new \DaemsModule\Forum\Infrastructure\SqlForumReportRepository(
            $c->make(Connection::class),
        ),
    );
    $container->singleton(
        \Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class,
        static fn(Container $c) => new \DaemsModule\Forum\Infrastructure\SqlForumModerationAuditRepository(
            $c->make(Connection::class),
        ),
    );
    $container->singleton(
        \Daems\Domain\Forum\ForumUserWarningRepositoryInterface::class,
        static fn(Container $c) => new \DaemsModule\Forum\Infrastructure\SqlForumUserWarningRepository(
            $c->make(Connection::class),
        ),
    );

    // ---------------------------------------------------------------------
    // 8× Public use cases (DaemsModule\Forum\Application\Forum\*)
    // ---------------------------------------------------------------------
    $container->bind(
        \DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\GetForumCategory\GetForumCategory::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\GetForumCategory\GetForumCategory(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\GetForumThread\GetForumThread::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\GetForumThread\GetForumThread(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\CreateForumTopic\CreateForumTopic::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\CreateForumTopic\CreateForumTopic(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\User\UserRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\CreateForumPost\CreateForumPost::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\CreateForumPost\CreateForumPost(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\User\UserRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\LikeForumPost\LikeForumPost::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\LikeForumPost\LikeForumPost(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\IncrementTopicView\IncrementTopicView::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\IncrementTopicView\IncrementTopicView(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Forum\ReportForumTarget\ReportForumTarget::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Forum\ReportForumTarget\ReportForumTarget(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Dismissal\AdminApplicationDismissalRepositoryInterface::class),
        ),
    );

    // ---------------------------------------------------------------------
    // 22× Admin use cases (DaemsModule\Forum\Application\Backstage\Forum\*)
    // ---------------------------------------------------------------------
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdmin(
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetail::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetail(
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDelete::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDelete(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLock::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLock(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarn::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarn(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumUserWarningRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEdit::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEdit(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReport::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReport(
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopic::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopic(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopic::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopic(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopic::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopic(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopic::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopic(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUser::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUser(
            $c->make(\Daems\Domain\Forum\ForumUserWarningRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdmin(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdmin::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdmin(
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStats::class,
        static fn(Container $c) => new \DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStats(
            $c->make(\Daems\Domain\Forum\ForumRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumReportRepositoryInterface::class),
            $c->make(\Daems\Domain\Forum\ForumModerationAuditRepositoryInterface::class),
        ),
    );

    // ---------------------------------------------------------------------
    // Dashboard widgets — registered with the platform's WidgetRegistry singleton
    // (already bound by daems-platform/bootstrap/app.php before module bindings run).
    // ---------------------------------------------------------------------
    $registry = $container->make(\Daems\Domain\Dashboard\WidgetRegistry::class);
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\ReportsKpiWidget());
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\PostsTodayKpiWidget());
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\FlaggedUsersKpiWidget());
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\PinnedTopicsKpiWidget());
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\ReportsQueueWidget());
    $registry->register(new \DaemsModule\Forum\Frontend\Backstage\Widgets\RecentForumPostsListWidget());

    // ---------------------------------------------------------------------
    // 3× Controllers
    // ---------------------------------------------------------------------
    $container->bind(
        \DaemsModule\Forum\Controller\ForumController::class,
        static fn(Container $c) => new \DaemsModule\Forum\Controller\ForumController(
            $c->make(\DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories::class),
            $c->make(\DaemsModule\Forum\Application\Forum\GetForumCategory\GetForumCategory::class),
            $c->make(\DaemsModule\Forum\Application\Forum\GetForumThread\GetForumThread::class),
            $c->make(\DaemsModule\Forum\Application\Forum\CreateForumTopic\CreateForumTopic::class),
            $c->make(\DaemsModule\Forum\Application\Forum\CreateForumPost\CreateForumPost::class),
            $c->make(\DaemsModule\Forum\Application\Forum\LikeForumPost\LikeForumPost::class),
            $c->make(\DaemsModule\Forum\Application\Forum\IncrementTopicView\IncrementTopicView::class),
            $c->make(\DaemsModule\Forum\Application\Forum\ReportForumTarget\ReportForumTarget::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Controller\ForumModerationBackstageController::class,
        static fn(Container $c) => new \DaemsModule\Forum\Controller\ForumModerationBackstageController(
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetail::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLock::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarn::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDelete::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEdit::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReport::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopic::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopic::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUser::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdmin::class),
        ),
    );
    $container->bind(
        \DaemsModule\Forum\Controller\ForumContentBackstageController::class,
        static fn(Container $c) => new \DaemsModule\Forum\Controller\ForumContentBackstageController(
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopic::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopic::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdmin::class),
            $c->make(\DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStats::class),
        ),
    );
};
