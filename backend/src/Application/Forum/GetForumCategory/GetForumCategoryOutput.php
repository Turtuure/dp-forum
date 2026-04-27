<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\GetForumCategory;

final class GetForumCategoryOutput
{
    public function __construct(
        public readonly ?array $data,
    ) {}
}
