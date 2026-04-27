<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Controller;

use DaemsModule\Forum\Application\Forum\CreateForumPost\CreateForumPost;
use DaemsModule\Forum\Application\Forum\CreateForumPost\CreateForumPostInput;
use DaemsModule\Forum\Application\Forum\CreateForumTopic\CreateForumTopic;
use DaemsModule\Forum\Application\Forum\CreateForumTopic\CreateForumTopicInput;
use DaemsModule\Forum\Application\Forum\GetForumCategory\GetForumCategory;
use DaemsModule\Forum\Application\Forum\GetForumCategory\GetForumCategoryInput;
use DaemsModule\Forum\Application\Forum\GetForumThread\GetForumThread;
use DaemsModule\Forum\Application\Forum\GetForumThread\GetForumThreadInput;
use DaemsModule\Forum\Application\Forum\IncrementTopicView\IncrementTopicView;
use DaemsModule\Forum\Application\Forum\IncrementTopicView\IncrementTopicViewInput;
use DaemsModule\Forum\Application\Forum\LikeForumPost\LikeForumPost;
use DaemsModule\Forum\Application\Forum\LikeForumPost\LikeForumPostInput;
use DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories;
use DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategoriesInput;
use DaemsModule\Forum\Application\Forum\ReportForumTarget\ReportForumTarget;
use DaemsModule\Forum\Application\Forum\ReportForumTarget\ReportForumTargetInput;
use Daems\Domain\Forum\TopicLockedException;
use Daems\Domain\Shared\NotFoundException;
use Daems\Domain\Tenant\Tenant;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use InvalidArgumentException;

final class ForumController
{
    public function __construct(
        private readonly ListForumCategories $listCategories,
        private readonly GetForumCategory $getCategory,
        private readonly GetForumThread $getThread,
        private readonly CreateForumTopic $createTopic,
        private readonly CreateForumPost $createPost,
        private readonly LikeForumPost $likePostUseCase,
        private readonly IncrementTopicView $incrementViewUseCase,
        private readonly ReportForumTarget $reportForumTarget,
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $output = $this->listCategories->execute(new ListForumCategoriesInput($tenantId));
        return Response::json(['data' => $output->categories]);
    }

    public function category(Request $request, array $params): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $output = $this->getCategory->execute(new GetForumCategoryInput($tenantId, $params['slug']));

        if ($output->data === null) {
            return Response::notFound('Category not found');
        }

        return Response::json(['data' => $output->data]);
    }

    public function thread(Request $request, array $params): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $output = $this->getThread->execute(new GetForumThreadInput($tenantId, $params['slug']));

        if ($output->data === null) {
            return Response::notFound('Thread not found');
        }

        return Response::json(['data' => $output->data]);
    }

    private function requireTenant(Request $request): Tenant
    {
        $tenant = $request->attribute('tenant');
        if (!$tenant instanceof Tenant) {
            throw new NotFoundException('unknown_tenant');
        }
        return $tenant;
    }

    public function createTopic(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $title   = trim($request->string('title') ?? '');
        $content = trim($request->string('content') ?? '');

        if ($title === '' || $content === '') {
            return Response::badRequest('Title and content are required.');
        }

        $output = $this->createTopic->execute(new CreateForumTopicInput(
            $acting,
            $params['slug'],
            $title,
            $content,
        ));

        if ($output->error !== null) {
            return Response::notFound($output->error);
        }

        return Response::json(['data' => ['slug' => $output->topicSlug]], 201);
    }

    public function createPost(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $content = trim($request->string('content') ?? '');

        if ($content === '') {
            return Response::badRequest('Content is required.');
        }

        try {
            $output = $this->createPost->execute(new CreateForumPostInput(
                $acting,
                $params['slug'],
                $content,
            ));
        } catch (TopicLockedException) {
            return Response::conflict('topic_locked');
        }

        if (!$output->success) {
            return Response::notFound($output->error ?? 'Thread not found.');
        }

        return Response::json(['data' => $output->post], 201);
    }

    public function likePost(Request $request, array $params): Response
    {
        $request->requireActingUser();
        $this->likePostUseCase->execute(new LikeForumPostInput($params['id']));
        return Response::json(['data' => ['ok' => true]]);
    }

    public function incrementView(Request $request, array $params): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $this->incrementViewUseCase->execute(new IncrementTopicViewInput($tenantId, $params['slug']));
        return Response::json(['data' => ['ok' => true]]);
    }

    public function createReport(Request $request): Response
    {
        $acting = $request->requireActingUser();
        $targetType = (string) ($request->string('target_type') ?? '');
        $targetId   = (string) ($request->string('target_id') ?? '');
        $reason     = (string) ($request->string('reason_category') ?? '');
        $detail     = $request->string('reason_detail');

        try {
            $this->reportForumTarget->execute(new ReportForumTargetInput(
                $acting,
                $targetType,
                $targetId,
                $reason,
                $detail,
            ));
        } catch (NotFoundException) {
            return Response::notFound('Target not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true]], 201);
    }
}
