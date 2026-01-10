<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Ramsey\Uuid\Uuid;

class Identity
{
    public readonly string $clientId;

    public readonly string $namespace;

    public function __construct(
        string $clientId = '',
        string $namespace = '',
    ) {
        $this->clientId = $clientId ?: self::generateClientId();
        $this->namespace = $namespace;
    }

    public static function generateClientId(): string
    {
        return (string) Uuid::uuid4();
    }
}
