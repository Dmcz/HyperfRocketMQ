<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\MessageType;

enum MessageKind
{
    case Normal;

    public function toProtobuf(): int
    {
        return match ($this) {
            self::Normal => MessageType::NORMAL,
        };
    }
}
