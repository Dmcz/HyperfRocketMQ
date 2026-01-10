<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Ramsey\Uuid\Uuid;

class MetadataFactory
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $namespace,
    ) {
    }

    public function create()
    {
        $now = gmdate('Ymd\THis\Z'); // 20060102T150405Z

        return [
            'x-mq-language' => 'PHP',
            'x-mq-protocol' => 'v2',
            'x-mq-request-id' => Uuid::uuid4(),
            'x-mq-client-version' => 'dev',
            'x-mq-client-id' => $this->clientId,
            'x-mq-namespace' => $this->namespace,
            'x-mq-date-time' => $now,
            'authorization' => sprintf(
                '%s %s=%s/%s/%s, %s=%s, %s=%s',
                'MQv2-HMAC-SHA1',
                'Credential',
                '', // TODO accessKey
                '',
                'Rocketmq',
                'SignedHeaders',
                'x-mq-date-time',
                'Signature',
                hash_hmac('sha1', $now, '') // TODO accessSecret
            ),
        ];
    }
}
