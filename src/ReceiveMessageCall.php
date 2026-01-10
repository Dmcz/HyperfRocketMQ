<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\AckMessageEntry;
use Apache\Rocketmq\V2\Code;
use Apache\Rocketmq\V2\Message;
use Apache\Rocketmq\V2\MessageQueue;
use Apache\Rocketmq\V2\ReceiveMessageResponse;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\Status;
use Dmcz\HyperfRocketmq\Exception\CriticalError;
use Dmcz\HyperfRocketmq\Traits\LoggerTrait;
use Dmcz\HyperfRocketmq\Traits\ResponseStatusAssertTrait;
use Google\Protobuf\Timestamp;
use Hyperf\GrpcClient\ServerStreamingCall;
use Psr\Log\LoggerInterface;

class ReceiveMessageCall
{
    use ResponseStatusAssertTrait;
    use LoggerTrait;

    public function __construct(
        protected ServerStreamingCall $call,
        protected MessageQueue $messageQueue,
        protected Resource $group,
        protected Resource $topic,
        protected ConnectionManager $connectionManager,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return ReceivedMessage[]
     */
    public function recevie(): ?array
    {
        if (empty($this->call->getStreamId())) {
            return null;
        }

        /** @var ReceiveMessageResponse[] */
        $responses = [];

        // 先接受全部
        while (true) {
            $received = $this->call->recv();
            if ($received === null || empty($received[0])) {
                break;
            }

            /* @var ReceiveMessageResponse */
            $responses[] = $received[0];
        }

        /** @var null|Status */
        $status = null;
        /** @var Message[] */
        $messages = [];
        /** @var null|Timestamp */
        $delivery = null;

        foreach ($responses as $resp) {
            switch ($resp->getContent()) {
                case 'status':
                    // 对status做一个监控
                    // 当多个的时候允许后一个覆盖前一个
                    // 暂时未发现多个的情况
                    if ($status !== null) {
                        $this->warning('Mulit status');
                    }

                    if ($resp->getStatus() === null) {
                        $this->warning('The status is null');
                        continue 2;
                    }

                    $status = $resp->getStatus();
                    break;
                case 'message':
                    if ($resp->getMessage() === null) {
                        $this->warning('The message is null');
                        continue 2;
                    }

                    $messages[] = $resp->getMessage();
                    break;
                case 'delivery_timestamp':
                    // 对delivery做一个监控，
                    // 当多个的时候允许后一个覆盖前一个
                    // 暂时未发现多个的情况
                    if ($delivery !== null) {
                        $this->warning('Mulit delivery');
                    }

                    if ($resp->getDeliveryTimestamp() === null) {
                        $this->warning('The delivery is null');
                        continue 2;
                    }

                    $delivery = $resp->getDeliveryTimestamp();
                    break;
                default:
                    // TODO
                    throw new CriticalError('Undifined: ' . $resp->getContent());
            }
        }

        if ($status === null) {
            // TODO
            throw new CriticalError('The status is null');
        }

        // 没有pull到消息
        if ($status->getCode() == Code::MESSAGE_NOT_FOUND) {
            $this->debug('No message');
            return null;
        }

        $this->assertResponseOk($status, 'ReceiveTelemetryCommand');

        $results = [];
        foreach ($messages as $msg) {
            $results[] = ReceivedMessage::fromProtobuf($msg, $delivery);
        }

        return $results;
    }

    /**
     * @param ReceivedMessage|ReceivedMessage[] $message
     */
    public function ack(array|ReceivedMessage $message): void
    {
        $entries = [];

        if (! is_array($message)) {
            $message = [$message];
        }

        foreach ($message as $msg) {
            $entry = new AckMessageEntry();
            $entry->setMessageId($msg->messageId);
            $entry->setReceiptHandle($msg->receiptHandle);

            $entries[] = $entry;
        }

        $this->connectionManager->ackMessage(
            $this->messageQueue->getBroker()->getEndpoints(),
            $entries,
            group: $this->group,
            topic: $this->topic,
        );
    }
}
