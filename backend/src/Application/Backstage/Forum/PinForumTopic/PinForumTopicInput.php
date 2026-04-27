<?php

declare(strict_types=1);

namespace DaemsModule\Forum\Application\Backstage\Forum\PinForumTopic;

use Daems\Domain\Auth\ActingUser;

final class PinForumTopicInput
{
    public function __construct(
        public readonly ActingUser $acting,
        public readonly string $topicId,
    ) {}
}
