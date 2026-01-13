<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Psr\Log\LoggerInterface;

class Producer
{
    protected ProducerSession $session;

    public function __construct(
        Target $target,
        protected ?LoggerInterface $logger = null
    ) {
        $this->session = new ProducerSession(
            new ProducerSettings(
                target: $target,
                identity: new Identity(),
            ),
            logger: $logger,
        );
    }

    public function start()
    {
        $this->session->start();
    }

    public function sendMessage(array $messages)
    {
        $this->session->sendMessage($messages);
    }
}
