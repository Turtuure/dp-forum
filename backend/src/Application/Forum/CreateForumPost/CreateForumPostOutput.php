<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\CreateForumPost;

final class CreateForumPostOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly ?array $post = null,
    ) {}
}
