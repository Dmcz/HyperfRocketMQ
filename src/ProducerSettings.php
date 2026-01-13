<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

class ProducerSettings extends SessionSettings
{
    public function __construct(
        Target $target,
        Identity $identity,
        int $requestTimeout = 3,
        public readonly bool $validateMessageType = true,
        public readonly int $maxBodySizeBytes = 4 * 1024 * 1024
    ) {
        parent::__construct($target, $identity, $requestTimeout);
    }
}
