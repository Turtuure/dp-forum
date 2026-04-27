<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\GetForumThread;

use Daems\Domain\Tenant\TenantId;

final class GetForumThreadInput
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly string $topicSlug,
    ) {}
}
