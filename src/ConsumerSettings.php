<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

class ConsumerSettings extends SessionSettings
{
    public function __construct(
        Target $target,
        Identity $identity,
        public readonly string $group,
        public readonly ConsumerType $type = ConsumerType::SimpleConsumer,
        public readonly int $receiveBatchSize = 32,
        public readonly int $longPollingTimeout = 30,
        int $requestTimeout = 3,
        ?string $hostname = null,
    ) {
        parent::__construct($target, $identity, $requestTimeout, $hostname);
    }
}
