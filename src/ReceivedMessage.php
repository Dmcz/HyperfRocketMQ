<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\Message;
use Google\Protobuf\Timestamp;

final class ReceivedMessage
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $topic,
        public readonly string $body,
        public readonly array $properties,
        public readonly string $tag,
        public readonly array $keys,
        public readonly string $messageGroup,
        public readonly ?int $deliveryTimestamp,
        public readonly int $deliveryAttempt,
        public readonly string $bornHost,
        public readonly int $bornTimestamp,
        public readonly string $traceContext,
        public readonly string $receiptHandle,
    ) {
    }

    // TODO 这里消息要重新设计
    // 1. 根据消息类型提供不同的字段
    // 2. 考虑一下没收到的deliveryTimestamp 和 投递的deliveryTimestamp
    public static function fromProtobuf(Message $message, ?Timestamp $deliveryTimestamp): static
    {
        $systemProperties = $message->getSystemProperties();

        return new self(
            messageId: $systemProperties->getMessageId(),
            topic: $message->getTopic()->getName(),
            body: $message->getBody(),
            properties: iterator_to_array($message->getUserProperties(), true),
            tag: $systemProperties->getTag(),
            keys: iterator_to_array($systemProperties->getKeys()),
            messageGroup: $systemProperties->getMessageGroup(),
            deliveryTimestamp: $deliveryTimestamp?->getSeconds(),
            deliveryAttempt: $systemProperties->getDeliveryAttempt(),
            bornHost: $systemProperties->getBornHost(),
            bornTimestamp: $systemProperties->getBornTimestamp()->getSeconds(),
            traceContext: $systemProperties->getTraceContext(),
            receiptHandle: $systemProperties->getReceiptHandle(),
        );
    }
}
