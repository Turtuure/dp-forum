<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Frontend\Backstage\Widgets;

use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\Widget;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Dashboard\WidgetSpan;
use Daems\Domain\Tenant\TenantId;
use Daems\Domain\User\User;
use Daems\Infrastructure\Dashboard\WidgetRenderer;

final class PostsTodayKpiWidget extends Widget
{
    public function __construct() {}

    public function id(): string             { return 'forum.posts_today_kpi'; }
    public function category(): WidgetCategory { return WidgetCategory::Numbers; }
    public function defaultSpan(): WidgetSpan  { return WidgetSpan::of(1); }
    public function minRole(): MinRole         { return MinRole::Moderator; }
    public function module(): string           { return 'forum'; }
    public function labelKey(): string         { return 'backstage.dashboard.widget.posts_today_kpi.label'; }
    public function descriptionKey(): string   { return 'backstage.dashboard.widget.posts_today_kpi.description'; }

    public function render(TenantId $tenantId, User $user): string
    {
        $d = $this->data($tenantId);
        return WidgetRenderer::kpi($d['value'], $d['change']);
    }

    public function data(TenantId $tenantId): array
    {
        // TODO(v1+): wire ListForumStats posts-created-today count
        // (use case requires ActingUser; widget contract only exposes TenantId).
        return ['value' => 0, 'change' => 0.0];
    }
}
