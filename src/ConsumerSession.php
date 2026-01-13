<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\ClientType;
use Apache\Rocketmq\V2\FilterExpression;
use Apache\Rocketmq\V2\FilterType;
use Apache\Rocketmq\V2\Publishing;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\Subscription;
use Apache\Rocketmq\V2\SubscriptionEntry;
use Dmcz\HyperfRocketmq\Exception\CriticalError;
use Google\Protobuf\Duration;
use Psr\Log\LoggerInterface;

class ConsumerSession extends Session
{
    /**
     * @var array<string,Topic>
     */
    protected array $subscriptions = [];

    private Resource $consumerGroup;

    public function __construct(
        private readonly ConsumerSettings $settings,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct($settings, logger: $logger);

        $this->consumerGroup = new Resource();
        $this->consumerGroup->setName($settings->group);
        $this->consumerGroup->setResourceNamespace($settings->identity->namespace);
    }

    public function registerTopic(Topic $topic): void
    {
        if (isset($this->subscriptions[$topic->topic])) {
            throw new CriticalError('The topic registered.');
        }

        $this->subscriptions[$topic->topic] = $topic;
    }

    public function unregisterTopic(Topic $topic): void
    {
        unset($this->subscriptions[$topic->topic]);
    }

    public function receiveMessage(Topic $topic, int $batchSize, int $invisibleDuration, int $longPollingTimeout): ReceiveMessageCall
    {
        if (! isset($this->subscriptions[$topic->topic])) {
            throw new CriticalError('The topic not subscribed.');
        }

        $queues = $this->connectionManager->queryRoute(
            $this->settings->target->toEndpoints(),
            $topic->topicResource()
        );

        if (empty($queues)) {
            // TODO
            throw new CriticalError('The queue is empty.');
        }

        // TODO 暂时先取第一个
        $queue = $queues[0];

        $group = $this->getGroup();

        $this->debug('Found %d queues; The id=%d was selected for message receive.', count($queues), $queue->getId());

        $serverStreamingCall = $this->connectionManager->receiveMessage(
            $queue->getBroker()->getEndpoints(),
            $batchSize,
            $topic->filterExpression(),
            $this->getGroup(),
            (new Duration())->setSeconds($invisibleDuration),
            new Duration()->setSeconds($longPollingTimeout),
            $queue,
            $this->settings->requestTimeout + $longPollingTimeout,
        );

        return new ReceiveMessageCall(
            call: $serverStreamingCall,
            messageQueue: $queue,
            group: $group,
            topic: $topic->topicResource(),
            connectionManager: $this->connectionManager,
            logger: $this->logger,
        );
    }

    protected function getClientType(): int
    {
        return match ($this->settings->type) {
            ConsumerType::SimpleConsumer => ClientType::SIMPLE_CONSUMER,
            default => throw new CriticalError('The consumer type not support.'),
        };
    }

    protected function getGroup(): ?Resource
    {
        return $this->consumerGroup;
    }

    protected function getSubscription(): Subscription
    {
        $subscription = new Subscription();
        // $subscription->setFifo();
        $subscription->setGroup($this->consumerGroup);
        $subscription->setLongPollingTimeout((new Duration())->setSeconds($this->settings->longPollingTimeout));
        $subscription->setReceiveBatchSize($this->settings->receiveBatchSize);

        /** @var SubscriptionEntry[] */
        $entries = [];

        foreach ($this->subscriptions as $topic) {
            $expression = new FilterExpression();
            $expression->setExpression($topic->expression);
            $expression->setType(match ($topic->expressionType) {
                ExpressionType::Sql => FilterType::SQL,
                ExpressionType::Tag => FilterType::TAG,
            });

            $entry = new SubscriptionEntry();
            $entry->setExpression($expression);
            $entry->setTopic($topic->topicResource());

            $entries[] = $entry;
        }

        $subscription->setSubscriptions($entries);

        return $subscription;
    }

    protected function getPublishing(): ?Publishing
    {
        return null;
    }
}
