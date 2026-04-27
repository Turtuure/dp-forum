<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Backstage\Forum\WarnForumUser;

use Daems\Domain\Auth\ActingUser;

final class WarnForumUserInput
{
    public function __construct(
        public readonly ActingUser $acting,
        public readonly string $userId,
        public readonly string $reason,
    ) {}
}
