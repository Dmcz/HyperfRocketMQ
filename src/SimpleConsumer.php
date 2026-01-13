<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Psr\Log\LoggerInterface;

class SimpleConsumer
{
    protected ConsumerSession $session;

    protected bool $started = false;

    public function __construct(
        Target $target,
        string $group,
        protected ?LoggerInterface $logger = null
    ) {
        $this->session = new ConsumerSession(
            new ConsumerSettings(
                target: $target,
                identity: new Identity(),
                group: $group,
            ),
            logger: $logger,
        );
    }

    public function start()
    {
        $this->session->start();
        $this->started = true;
    }

    public function subscribe(Topic $topic)
    {
        $this->session->registerTopic($topic);
        if ($this->started) {
            $this->session->syncSetting();
        }
    }

    public function receive(Topic $topic): ReceiveMessageCall
    {
        return $this->session->receiveMessage($topic, 16, 20, 5);
    }
}
