<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Forum\GetForumThread;

final class GetForumThreadOutput
{
    public function __construct(
        public readonly ?array $data,
    ) {}
}
