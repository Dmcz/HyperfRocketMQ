<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\ClientType;
use Apache\Rocketmq\V2\Publishing;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\Subscription;
use Psr\Log\LoggerInterface;

class ProducerSession extends Session
{
    /**
     * @var array<string,Topic>
     */
    protected array $topics = [];

    public function __construct(
        private readonly ProducerSettings $settings,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct($settings, logger: $logger);
    }

    public function sendMessage(array $messages)
    {
        // TODO many things...
        $msgArr = [];
        foreach ($messages as $message) {
            $msgArr[] = $message->toProtobuf();
        }

        // TODO check
        $this->connectionManager->sendMessage($this->defaultEndpoints, $msgArr);
    }

    public function getPublishing(): Publishing
    {
        $publishing = new Publishing();

        $publishing->setMaxBodySize($this->settings->maxBodySizeBytes);

        $topics = [];
        foreach ($this->topics as $topic) {
            $topics[] = $topic->topicResource();
        }

        $publishing->setTopics($topics);

        $publishing->setValidateMessageType($this->settings->validateMessageType);

        return $publishing;
    }

    public function getClientType(): int
    {
        return ClientType::PRODUCER;
    }

    public function getGroup(): ?Resource
    {
        return null;
    }

    public function getSubscription(): ?Subscription
    {
        return null;
    }
}
