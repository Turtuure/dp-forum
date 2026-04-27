<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Controller;

use Daems\Domain\Auth\ForbiddenException;
use Daems\Domain\Shared\ConflictException;
use Daems\Domain\Shared\NotFoundException;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumPostAsAdmin\DeleteForumPostAsAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumTopicAsAdmin\DeleteForumTopicAsAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReport;
use DaemsModule\Forum\Application\Backstage\Forum\DismissForumReport\DismissForumReportInput;
use DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\EditForumPostAsAdmin\EditForumPostAsAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetail;
use DaemsModule\Forum\Application\Backstage\Forum\GetForumReportDetail\GetForumReportDetailInput;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumModerationAuditForAdmin\ListForumModerationAuditForAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumReportsForAdmin\ListForumReportsForAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\LockForumTopic\LockForumTopicInput;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDelete;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByDelete\ResolveForumReportByDeleteInput;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEdit;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByEdit\ResolveForumReportByEditInput;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLock;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByLock\ResolveForumReportByLockInput;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarn;
use DaemsModule\Forum\Application\Backstage\Forum\ResolveForumReportByWarn\ResolveForumReportByWarnInput;
use DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\UnlockForumTopic\UnlockForumTopicInput;
use DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUser;
use DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser\WarnForumUserInput;
use InvalidArgumentException;

final class ForumModerationBackstageController
{
    public function __construct(
        private readonly ListForumReportsForAdmin $listReports,
        private readonly GetForumReportDetail $getReport,
        private readonly ResolveForumReportByLock $resolveByLock,
        private readonly ResolveForumReportByWarn $resolveByWarn,
        private readonly ResolveForumReportByDelete $resolveByDelete,
        private readonly ResolveForumReportByEdit $resolveByEdit,
        private readonly DismissForumReport $dismissReport,
        private readonly LockForumTopic $lockTopic,
        private readonly UnlockForumTopic $unlockTopic,
        private readonly DeleteForumTopicAsAdmin $deleteTopic,
        private readonly EditForumPostAsAdmin $editPost,
        private readonly DeleteForumPostAsAdmin $deletePost,
        private readonly WarnForumUser $warnUser,
        private readonly ListForumModerationAuditForAdmin $listAudit,
    ) {
    }

    public function listForumReports(Request $request): Response
    {
        $acting = $request->requireActingUser();

        /** @var array{status?:string, target_type?:string} $filters */
        $filters = [];
        $status = $request->string('status');
        $targetType = $request->string('target_type');
        if (is_string($status) && $status !== '') {
            $filters['status'] = $status;
        }
        if (is_string($targetType) && $targetType !== '') {
            $filters['target_type'] = $targetType;
        }
        $limitRaw = $request->string('limit');
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : 50;

        try {
            $out = $this->listReports->execute(new ListForumReportsForAdminInput($acting, $filters, $limit));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json(['data' => array_map(static fn($a) => [
            'compound_id'   => $a->compoundKey(),
            'target_type'   => $a->targetType,
            'target_id'     => $a->targetId,
            'report_count'  => $a->reportCount,
            'reason_counts' => $a->reasonCounts,
            'earliest'      => $a->earliestCreatedAt,
            'latest'        => $a->latestCreatedAt,
            'status'        => $a->status,
        ], $out->items)]);
    }

    /**
     * @param array<string,string> $params
     */
    public function getForumReport(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $compoundId = (string) ($params['id'] ?? '');
        if (!preg_match('/^(post|topic):([0-9a-f\-]{36})$/', $compoundId, $m)) {
            return Response::badRequest('Invalid compound id');
        }
        $targetType = $m[1];
        $targetId = $m[2];

        try {
            $out = $this->getReport->execute(new GetForumReportDetailInput($acting, $targetType, $targetId));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Report not found');
        }

        $aggregated = $out->aggregated;
        return Response::json(['data' => [
            'aggregated' => [
                'compound_id'   => $aggregated->compoundKey(),
                'target_type'   => $aggregated->targetType,
                'target_id'     => $aggregated->targetId,
                'report_count'  => $aggregated->reportCount,
                'reason_counts' => $aggregated->reasonCounts,
                'earliest'      => $aggregated->earliestCreatedAt,
                'latest'        => $aggregated->latestCreatedAt,
                'status'        => $aggregated->status,
            ],
            'raw_reports' => array_map(static fn($r) => [
                'id'               => $r->id()->value(),
                'reporter_user_id' => $r->reporterUserId(),
                'reason_category'  => $r->reasonCategory(),
                'reason_detail'    => $r->reasonDetail(),
                'status'           => $r->status(),
                'created_at'       => $r->createdAt(),
            ], $out->rawReports),
            'target_content' => $out->targetContent,
        ]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function resolveForumReport(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $compoundId = (string) ($params['id'] ?? '');
        if (!preg_match('/^(post|topic):([0-9a-f\-]{36})$/', $compoundId, $m)) {
            return Response::badRequest('Invalid compound id');
        }
        $targetType = $m[1];
        $targetId = $m[2];
        $action = (string) ($request->string('action') ?? '');
        $note = $request->string('note');

        try {
            switch ($action) {
                case 'delete':
                    $this->resolveByDelete->execute(
                        new ResolveForumReportByDeleteInput($acting, $targetType, $targetId, $note),
                    );
                    break;
                case 'lock':
                    $this->resolveByLock->execute(
                        new ResolveForumReportByLockInput($acting, $targetType, $targetId, $note),
                    );
                    break;
                case 'warn':
                    $this->resolveByWarn->execute(
                        new ResolveForumReportByWarnInput($acting, $targetType, $targetId, $note),
                    );
                    break;
                case 'edit':
                    $newContent = (string) ($request->string('new_content') ?? '');
                    $this->resolveByEdit->execute(
                        new ResolveForumReportByEditInput($acting, $targetType, $targetId, $newContent, $note),
                    );
                    break;
                default:
                    return Response::badRequest('Unknown action');
            }
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Target not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        } catch (ConflictException $e) {
            return Response::conflict($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function dismissForumReport(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $compoundId = (string) ($params['id'] ?? '');
        if (!preg_match('/^(post|topic):([0-9a-f\-]{36})$/', $compoundId, $m)) {
            return Response::badRequest('Invalid compound id');
        }
        $targetType = $m[1];
        $targetId = $m[2];
        $note = $request->string('note');

        try {
            $this->dismissReport->execute(
                new DismissForumReportInput($acting, $targetType, $targetId, $note),
            );
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Report not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function lockForumTopic(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->lockTopic->execute(new LockForumTopicInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Topic not found');
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id, 'locked' => true]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function unlockForumTopic(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->unlockTopic->execute(new UnlockForumTopicInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Topic not found');
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id, 'locked' => false]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function deleteForumTopicAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->deleteTopic->execute(new DeleteForumTopicAsAdminInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Topic not found');
        }

        return Response::json(null, 204);
    }

    /**
     * @param array<string,string> $params
     */
    public function editForumPostAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        $newContent = (string) ($request->string('new_content') ?? '');
        $note = $request->string('note');

        try {
            $this->editPost->execute(new EditForumPostAsAdminInput($acting, $id, $newContent, $note));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Post not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function deleteForumPostAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->deletePost->execute(new DeleteForumPostAsAdminInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Post not found');
        }

        return Response::json(null, 204);
    }

    /**
     * @param array<string,string> $params
     */
    public function warnForumUser(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $userId = (string) ($params['id'] ?? '');
        $reason = (string) ($request->string('reason') ?? '');

        try {
            $this->warnUser->execute(new WarnForumUserInput($acting, $userId, $reason));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('User not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true, 'user_id' => $userId]], 201);
    }

    public function listForumAudit(Request $request): Response
    {
        $acting = $request->requireActingUser();

        /** @var array{action?:string, performer?:string} $filters */
        $filters = [];
        $action = $request->string('action');
        $performer = $request->string('performer');
        if (is_string($action) && $action !== '') {
            $filters['action'] = $action;
        }
        if (is_string($performer) && $performer !== '') {
            $filters['performer'] = $performer;
        }
        $limitRaw = $request->string('limit');
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : 200;
        $offsetRaw = $request->string('offset');
        $offset = is_numeric($offsetRaw) ? max(0, (int) $offsetRaw) : 0;

        try {
            $out = $this->listAudit->execute(
                new ListForumModerationAuditForAdminInput($acting, $limit, $filters, $offset),
            );
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json(['data' => array_map(static fn($e) => [
            'id'                => $e->id()->value(),
            'target_type'       => $e->targetType(),
            'target_id'         => $e->targetId(),
            'action'            => $e->action(),
            'original_payload'  => $e->originalPayload(),
            'new_payload'       => $e->newPayload(),
            'reason'            => $e->reason(),
            'performed_by'      => $e->performedBy(),
            'related_report_id' => $e->relatedReportId(),
            'created_at'        => $e->createdAt(),
        ], $out->entries)]);
    }
}
