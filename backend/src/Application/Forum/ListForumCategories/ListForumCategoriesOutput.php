<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\ListForumCategories;

final class ListForumCategoriesOutput
{
    public function __construct(
        public readonly array $categories,
    ) {}
}
