<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Tests\Unit\Controller;

use DaemsModule\Forum\Controller\ForumModerationBackstageController;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetail;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLock;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarn;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDelete;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEdit;
use DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReport;
use DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUser;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdmin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class ForumModerationBackstageControllerTest extends TestCase
{
    public function test_constructor_signature_is_stable(): void
    {
        $reflection = new ReflectionClass(ForumModerationBackstageController::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);

        $paramTypes = array_map(
            fn(ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        self::assertSame([
            ListForumReportsForAdmin::class,
            GetForumReportDetail::class,
            ResolveForumReportByLock::class,
            ResolveForumReportByWarn::class,
            ResolveForumReportByDelete::class,
            ResolveForumReportByEdit::class,
            DismissForumReport::class,
            LockForumTopic::class,
            UnlockForumTopic::class,
            DeleteForumTopicAsAdmin::class,
            EditForumPostAsAdmin::class,
            DeleteForumPostAsAdmin::class,
            WarnForumUser::class,
            ListForumModerationAuditForAdmin::class,
        ], $paramTypes);
    }

    public function test_has_all_11_moderation_methods(): void
    {
        $reflection = new ReflectionClass(ForumModerationBackstageController::class);
        $methods = array_map(
            fn(ReflectionMethod $m) => $m->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        );
        $methods = array_values(array_filter($methods, fn(string $m) => $m !== '__construct'));
        sort($methods);

        self::assertSame([
            'deleteForumPostAdmin',
            'deleteForumTopicAdmin',
            'dismissForumReport',
            'editForumPostAdmin',
            'getForumReport',
            'listForumAudit',
            'listForumReports',
            'lockForumTopic',
            'resolveForumReport',
            'unlockForumTopic',
            'warnForumUser',
        ], $methods);
    }
}
