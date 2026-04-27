<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\IncrementTopicView;

use Daems\Domain\Tenant\TenantId;

final class IncrementTopicViewInput
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly string $topicSlug,
    ) {}
}
