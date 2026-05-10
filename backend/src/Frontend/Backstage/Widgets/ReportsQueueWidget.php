<?php
declare(strict_types=1);

namespace DaemsModule\Forum\Frontend\Backstage\Widgets;

use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\Widget;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Dashboard\WidgetSpan;
use Daems\Domain\Tenant\TenantId;
use Daems\Domain\User\User;
use Daems\Frontend\I18n;
use Daems\Infrastructure\Dashboard\WidgetRenderer;

final class ReportsQueueWidget extends Widget
{
    public function __construct() {}

    public function id(): string             { return 'forum.reports_queue'; }
    public function category(): WidgetCategory { return WidgetCategory::Lists; }
    public function defaultSpan(): WidgetSpan  { return WidgetSpan::of(4); }
    public function minRole(): MinRole         { return MinRole::Moderator; }
    public function module(): string           { return 'forum'; }
    public function labelKey(): string         { return 'backstage.dashboard.widget.reports_queue.label'; }
    public function descriptionKey(): string   { return 'backstage.dashboard.widget.reports_queue.description'; }

    public function render(TenantId $tenantId, User $user): string
    {
        $d = $this->data($tenantId);
        return WidgetRenderer::list(
            I18n::t($this->labelKey()),
            array_values(array_map('strval', $d['items'])),
        );
    }

    public function data(TenantId $tenantId): array
    {
        // TODO(v1+): wire ListForumReportsForAdmin (full reports queue)
        // (use case requires ActingUser; widget contract only exposes TenantId).
        return ['items' => []];
    }
}
