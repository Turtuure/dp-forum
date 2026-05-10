<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Tests\Unit\Frontend\Backstage\Widgets;

use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Tenant\TenantId;
use DaemsModule\Forum\Frontend\Backstage\Widgets\FlaggedUsersKpiWidget;
use DaemsModule\Forum\Frontend\Backstage\Widgets\PinnedTopicsKpiWidget;
use DaemsModule\Forum\Frontend\Backstage\Widgets\PostsTodayKpiWidget;
use DaemsModule\Forum\Frontend\Backstage\Widgets\RecentForumPostsListWidget;
use DaemsModule\Forum\Frontend\Backstage\Widgets\ReportsKpiWidget;
use DaemsModule\Forum\Frontend\Backstage\Widgets\ReportsQueueWidget;
use PHPUnit\Framework\TestCase;

final class ForumWidgetsTest extends TestCase
{
    public function test_metadata_for_forum_widgets(): void
    {
        $cases = [
            ['forum.reports_kpi',        WidgetCategory::Numbers, 1, MinRole::Moderator, new ReportsKpiWidget()],
            ['forum.posts_today_kpi',    WidgetCategory::Numbers, 1, MinRole::Moderator, new PostsTodayKpiWidget()],
            ['forum.flagged_users_kpi',  WidgetCategory::Numbers, 1, MinRole::Moderator, new FlaggedUsersKpiWidget()],
            ['forum.pinned_topics_kpi',  WidgetCategory::Numbers, 1, MinRole::Moderator, new PinnedTopicsKpiWidget()],
            ['forum.reports_queue',      WidgetCategory::Lists,   4, MinRole::Moderator, new ReportsQueueWidget()],
            ['forum.recent_posts_list',  WidgetCategory::Lists,   2, MinRole::Moderator, new RecentForumPostsListWidget()],
        ];

        foreach ($cases as [$id, $cat, $span, $role, $w]) {
            self::assertSame($id, $w->id());
            self::assertSame($cat, $w->category());
            self::assertSame($span, $w->defaultSpan()->value());
            self::assertSame($role, $w->minRole());
            self::assertSame('forum', $w->module());
        }
    }

    public function test_reports_kpi_data_stub(): void
    {
        $w = new ReportsKpiWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame(0, $d['value']);
        self::assertSame(0.0, $d['change']);
    }

    public function test_posts_today_kpi_data_stub(): void
    {
        $w = new PostsTodayKpiWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame(0, $d['value']);
        self::assertSame(0.0, $d['change']);
    }

    public function test_flagged_users_kpi_data_stub(): void
    {
        $w = new FlaggedUsersKpiWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame(0, $d['value']);
        self::assertSame(0.0, $d['change']);
    }

    public function test_pinned_topics_kpi_data_stub(): void
    {
        $w = new PinnedTopicsKpiWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame(0, $d['value']);
        self::assertSame(0.0, $d['change']);
    }

    public function test_reports_queue_data_stub(): void
    {
        $w = new ReportsQueueWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame([], $d['items']);
    }

    public function test_recent_posts_list_data_stub(): void
    {
        $w = new RecentForumPostsListWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame([], $d['items']);
    }

    public function test_render_reports_kpi_returns_html(): void
    {
        $html = (new ReportsKpiWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    public function test_render_posts_today_kpi_returns_html(): void
    {
        $html = (new PostsTodayKpiWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    public function test_render_flagged_users_kpi_returns_html(): void
    {
        $html = (new FlaggedUsersKpiWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    public function test_render_pinned_topics_kpi_returns_html(): void
    {
        $html = (new PinnedTopicsKpiWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    public function test_render_reports_queue_returns_html(): void
    {
        $html = (new ReportsQueueWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    public function test_render_recent_posts_list_returns_html(): void
    {
        $html = (new RecentForumPostsListWidget())->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    private function fakeUser(): \Daems\Domain\User\User
    {
        $class = new \ReflectionClass(\Daems\Domain\User\User::class);
        return $class->newInstanceWithoutConstructor();
    }
}
