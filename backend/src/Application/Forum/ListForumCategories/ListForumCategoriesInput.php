<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\ListForumCategories;

use Daems\Domain\Tenant\TenantId;

final class ListForumCategoriesInput
{
    public function __construct(
        public readonly TenantId $tenantId,
    ) {}
}
