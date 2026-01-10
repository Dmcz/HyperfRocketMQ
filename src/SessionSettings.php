<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

class SessionSettings
{
    public function __construct(
        public readonly Target $target,
        public readonly Identity $identity,
        public readonly int $requestTimeout = 3,
        public readonly ?string $hostname = null,
    )
    {}
}