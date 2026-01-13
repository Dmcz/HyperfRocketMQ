<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\Encoding;
use Apache\Rocketmq\V2\Message;
use Apache\Rocketmq\V2\SystemProperties;
use Google\Protobuf\Timestamp;

final class PublishingMessage
{
    public function __construct(
        public readonly Topic $topic,
        public readonly string $messageId,
        public readonly string $body,
        public readonly array $keys,
        public readonly MessageKind $messageType,
        public readonly ?string $tag,
        public readonly ?string $traceContext,
        public readonly ?int $deliveryTimestamp,
        public readonly ?string $messageGroup,
        public readonly array $properties,
    ) {
    }

    public function toProtobuf(): Message
    {
        $systemProperties = new SystemProperties();
        $systemProperties->setKeys($this->keys);
        $systemProperties->setMessageId($this->messageId);
        $systemProperties->setBornTimestamp((new Timestamp())->setSeconds(time()));
        $systemProperties->setBornHost(Env::getHost());
        $systemProperties->setBodyEncoding(Encoding::IDENTITY);
        $systemProperties->setMessageType($this->messageType->toProtobuf());

        if ($this->tag !== null) {
            $systemProperties->setTag($this->tag);
        }

        if ($this->traceContext !== null) {
            $systemProperties->setTraceContext($this->traceContext);
        }

        if ($this->deliveryTimestamp !== null) {
            $systemProperties->setDeliveryTimestamp((new Timestamp())->setSeconds($this->deliveryTimestamp));
        }

        if ($this->messageGroup !== null) {
            $systemProperties->setMessageGroup($this->messageGroup);
        }

        $message = new Message();
        $message->setBody($this->body);
        $message->setSystemProperties($systemProperties);
        $message->setTopic($this->topic->topicResource());
        $message->setUserProperties($this->properties);

        return $message;
    }

    public static function normal(
        Topic $topic,
        string $body,
        array $keys = [],
        string $tag = '*',
        ?string $traceContext = null,
        array $properties = [],
    ): static {
        return new self(
            topic: $topic,
            messageId: MessageIdGenerator::instance()->nextId(),
            body: $body,
            keys: $keys,
            messageType: MessageKind::Normal,
            tag: $tag,
            traceContext: $traceContext,
            deliveryTimestamp: null,
            messageGroup: null,
            properties: $properties,
        );
    }
}
