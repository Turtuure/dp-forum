<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Tests\Unit\Controller;

use DaemsModule\Forum\Controller\ForumContentBackstageController;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumTopicsForAdmin\ListForumTopicsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic\PinForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\UnpinForumTopic\UnpinForumTopic;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumPostsForAdmin\ListForumPostsForAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\CreateForumCategoryAsAdmin\CreateForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\UpdateForumCategoryAsAdmin\UpdateForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\DeleteForumCategoryAsAdmin\DeleteForumCategoryAsAdmin;
use DaemsModule\Forum\Application\Backstage\Forum\ListForumStats\ListForumStats;
use DaemsModule\Forum\Application\Forum\ListForumCategories\ListForumCategories;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class ForumContentBackstageControllerTest extends TestCase
{
    public function test_constructor_signature_is_stable(): void
    {
        $reflection = new ReflectionClass(ForumContentBackstageController::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);

        $paramTypes = array_map(
            fn(ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters()
        );

        self::assertSame([
            ListForumTopicsForAdmin::class,
            PinForumTopic::class,
            UnpinForumTopic::class,
            ListForumPostsForAdmin::class,
            ListForumCategories::class,
            CreateForumCategoryAsAdmin::class,
            UpdateForumCategoryAsAdmin::class,
            DeleteForumCategoryAsAdmin::class,
            ListForumStats::class,
        ], $paramTypes);
    }

    public function test_has_all_9_content_methods(): void
    {
        $reflection = new ReflectionClass(ForumContentBackstageController::class);
        $methods = array_map(
            fn(ReflectionMethod $m) => $m->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        );
        $methods = array_values(array_filter($methods, fn(string $m) => $m !== '__construct'));
        sort($methods);

        self::assertSame([
            'createForumCategoryAdmin',
            'deleteForumCategoryAdmin',
            'listForumCategoriesAdmin',
            'listForumPostsAdmin',
            'listForumTopicsAdmin',
            'pinForumTopic',
            'statsForum',
            'unpinForumTopic',
            'updateForumCategoryAdmin',
        ], $methods);
    }
}
