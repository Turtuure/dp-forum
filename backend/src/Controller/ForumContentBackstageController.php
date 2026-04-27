<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Controller;

use Daems\Domain\Auth\ForbiddenException;
use Daems\Domain\Forum\ForumPost;
use Daems\Domain\Forum\ForumTopic;
use Daems\Domain\Shared\ConflictException;
use Daems\Domain\Shared\NotFoundException;
use Daems\Domain\Tenant\Tenant;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStats;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStatsInput;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdminInput;
use DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopicInput;
use DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopicInput;
use DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdminInput;
use DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories;
use DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategoriesInput;
use InvalidArgumentException;

final class ForumContentBackstageController
{
    public function __construct(
        private readonly ListForumTopicsForAdmin $listTopics,
        private readonly PinForumTopic $pinTopic,
        private readonly UnpinForumTopic $unpinTopic,
        private readonly ListForumPostsForAdmin $listPosts,
        private readonly ListForumCategories $listCategories,
        private readonly CreateForumCategoryAsAdmin $createCategory,
        private readonly UpdateForumCategoryAsAdmin $updateCategory,
        private readonly DeleteForumCategoryAsAdmin $deleteCategory,
        private readonly ListForumStats $listStats,
    ) {
    }

    public function listForumTopicsAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();

        $filters = [];
        foreach (['category_id', 'q', 'pinned_only', 'locked_only'] as $key) {
            $v = $request->input($key);
            if ($v !== null && $v !== '') {
                $filters[$key] = $v;
            }
        }
        $limitRaw = $request->string('limit');
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : 100;

        try {
            $out = $this->listTopics->execute(new ListForumTopicsForAdminInput($acting, $limit, $filters));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json(['data' => array_map(static fn(ForumTopic $t) => [
            'id'               => $t->id()->value(),
            'category_id'      => $t->categoryId(),
            'slug'             => $t->slug(),
            'title'            => $t->title(),
            'author_name'      => $t->authorName(),
            'user_id'          => $t->userId(),
            'pinned'           => $t->pinned(),
            'locked'           => $t->locked(),
            'reply_count'      => $t->replyCount(),
            'view_count'       => $t->viewCount(),
            'last_activity_at' => $t->lastActivityAt(),
            'created_at'       => $t->createdAt(),
        ], $out->topics)]);
    }

    /**
     * @param array<string,string> $params
     */
    public function pinForumTopic(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->pinTopic->execute(new PinForumTopicInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Topic not found');
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id, 'pinned' => true]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function unpinForumTopic(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->unpinTopic->execute(new UnpinForumTopicInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Topic not found');
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id, 'pinned' => false]]);
    }

    public function listForumPostsAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();

        $filters = [];
        foreach (['topic_id', 'q'] as $key) {
            $v = $request->input($key);
            if ($v !== null && $v !== '') {
                $filters[$key] = $v;
            }
        }
        $limitRaw = $request->string('limit');
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : 100;

        try {
            $out = $this->listPosts->execute(new ListForumPostsForAdminInput($acting, $limit, $filters));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json(['data' => array_map(static fn(ForumPost $p) => [
            'id'          => $p->id()->value(),
            'topic_id'    => $p->topicId(),
            'author_name' => $p->authorName(),
            'user_id'     => $p->userId(),
            'content'     => $p->content(),
            'likes'       => $p->likes(),
            'sort_order'  => $p->sortOrder(),
            'created_at'  => $p->createdAt(),
            'edited_at'   => $p->editedAt(),
        ], $out->posts)]);
    }

    public function listForumCategoriesAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();
        if (!$acting->isAdminIn($acting->activeTenant) && !$acting->isPlatformAdmin) {
            return Response::forbidden('Admin only');
        }

        $out = $this->listCategories->execute(new ListForumCategoriesInput($acting->activeTenant));
        return Response::json(['data' => $out->categories]);
    }

    public function createForumCategoryAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();
        $slug = (string) ($request->string('slug') ?? '');
        $name = (string) ($request->string('name') ?? '');
        $icon = (string) ($request->string('icon') ?? '');
        $description = (string) ($request->string('description') ?? '');
        $sortOrderRaw = $request->input('sort_order');
        $sortOrder = is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : 0;

        try {
            $out = $this->createCategory->execute(new CreateForumCategoryAsAdminInput(
                $acting, $slug, $name, $icon, $description, $sortOrder,
            ));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        } catch (ConflictException $e) {
            return Response::conflict($e->getMessage());
        }

        return Response::json(['data' => ['id' => $out->id, 'slug' => $out->slug]], 201);
    }

    /**
     * @param array<string,string> $params
     */
    public function updateForumCategoryAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        $sortOrderRaw = $request->input('sort_order');

        try {
            $this->updateCategory->execute(new UpdateForumCategoryAsAdminInput(
                $acting,
                $id,
                $request->string('slug'),
                $request->string('name'),
                $request->string('icon'),
                $request->string('description'),
                is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : null,
            ));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Category not found');
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        } catch (ConflictException $e) {
            return Response::conflict($e->getMessage());
        }

        return Response::json(['data' => ['ok' => true, 'id' => $id]]);
    }

    /**
     * @param array<string,string> $params
     */
    public function deleteForumCategoryAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');

        try {
            $this->deleteCategory->execute(new DeleteForumCategoryAsAdminInput($acting, $id));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        } catch (NotFoundException) {
            return Response::notFound('Category not found');
        } catch (ConflictException $e) {
            return Response::conflict($e->getMessage());
        }

        return Response::json(null, 204);
    }

    public function statsForum(Request $request): Response
    {
        $acting = $request->requireActingUser();
        $tenant = $this->requireTenant($request);

        try {
            $out = $this->listStats->execute(new ListForumStatsInput(
                acting:   $acting,
                tenantId: $tenant->id,
            ));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json([
            'data' => [
                'open_reports' => $out->stats['open_reports'],
                'topics'       => $out->stats['topics'],
                'categories'   => $out->stats['categories'],
                'mod_actions'  => $out->stats['mod_actions'],
                'recent_audit' => array_map(static fn ($e) => [
                    'when'        => $e->createdAt(),
                    'actor_id'    => $e->performedBy(),
                    'action'      => $e->action(),
                    'target_type' => $e->targetType(),
                    'target_id'   => $e->targetId(),
                    'reason'      => $e->reason(),
                ], $out->recentAudit),
            ],
        ]);
    }

    private function requireTenant(Request $request): Tenant
    {
        $tenant = $request->attribute('tenant');
        if (!$tenant instanceof Tenant) {
            throw new NotFoundException('unknown_tenant');
        }
        return $tenant;
    }
}
