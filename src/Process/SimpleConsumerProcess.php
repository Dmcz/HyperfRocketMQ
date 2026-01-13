<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Process;

use Dmcz\HyperfRocketmq\ReceivedMessage;
use Dmcz\HyperfRocketmq\SimpleConsumer;
use Dmcz\HyperfRocketmq\Target;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class SimpleConsumerProcess extends AbstractProcess
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function handle(): void
    {
        $consumer = new SimpleConsumer(
            target: Target::parse($this->getHostName()),
            group: $this->getGroup(),
            logger: $this->getLogger(),
        );

        foreach ($this->getTopics() as $topic) {
            $consumer->subscribe($topic);
        }

        $consumer->start();

        // TODO GracefulStop
        while (true) {
            try {
                $call = $consumer->receive($topic);

                $messages = $call->recevie();
                if ($messages !== null) {
                    // TODO try catch
                    foreach ($messages as $message) {
                        if ($this->onMessage($message)) {
                            $call->ack($message);
                        }
                    }
                }
            } catch (Throwable $th) {
                $this->onError($th);
            }

            sleep(1);
        }
    }

    abstract protected function onMessage(ReceivedMessage $messages): bool;

    abstract protected function onError(Throwable $th): void;

    abstract protected function getTopics(): array;

    // TODO 
    abstract protected function getHostName(): string;
    
    // TODO 
    abstract protected function getGroup(): string;

    protected function getLogger(): ?LoggerInterface
    {
        return null;
    }
}
