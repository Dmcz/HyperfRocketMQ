<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\AckMessageEntry;
use Apache\Rocketmq\V2\AckMessageRequest;
use Apache\Rocketmq\V2\Endpoints;
use Apache\Rocketmq\V2\FilterExpression;
use Apache\Rocketmq\V2\HeartbeatRequest;
use Apache\Rocketmq\V2\Message;
use Apache\Rocketmq\V2\MessageQueue;
use Apache\Rocketmq\V2\QueryRouteRequest;
use Apache\Rocketmq\V2\ReceiveMessageRequest;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\SendMessageRequest;
use Apache\Rocketmq\V2\SendMessageResponse;
use Apache\Rocketmq\V2\SendResultEntry;
use Dmcz\HyperfRocketmq\Stub\MessagingServiceClient;
use Dmcz\HyperfRocketmq\Traits\ResponseStatusAssertTrait;
use Google\Protobuf\Duration;
use Hyperf\GrpcClient\ServerStreamingCall;

class Connection
{
    use ResponseStatusAssertTrait;

    public function __construct(
        protected MessagingServiceClient $client,
    ) {
    }

    /**
     * @return MessageQueue[]
     */
    public function queryRoute(Resource $topic, Endpoints $endpoints, array $metadata): array
    {
        $requset = new QueryRouteRequest();
        $requset->setTopic($topic);
        $requset->setEndpoints($endpoints);

        [$response] = $this->client->QueryRoute($requset, $metadata);

        $this->assertResponseOk($response->getStatus(), 'QueryRoute');

        return iterator_to_array($response->getMessageQueues());
    }

    public function telemetry(array $metadata): Telemetry
    {
        return new Telemetry($this->client->Telemetry($metadata));
    }

    public function heartBeat(int $clientType, ?Resource $group, array $metadata): void
    {
        $request = new HeartbeatRequest();
        $request->setClientType($clientType);

        if ($group !== null) {
            $request->setGroup($group);
        }

        [$response] = $this->client->Heartbeat($request, $metadata);
        $this->assertResponseOk($response->getStatus(), 'HeartBeat');
    }

    /**
     * @param Message[] $messages
     * @return SendResultEntry[]
     */
    public function sendMessage(array $messages, array $metadata): array
    {
        $requset = new SendMessageRequest();
        $requset->setMessages($messages);

        $response = $this->client->SendMessage($requset, $metadata);

        /** @var SendMessageResponse */
        $reply = $this->extractReply($response, 'sendMessage');

        $this->assertResponseOk($reply->getStatus(), 'sendMessage');

        return iterator_to_array($reply->getEntries());
    }

    public function receiveMessage(int $batchSize, FilterExpression $filterExpression, Resource $group, Duration $invisibleDuration, Duration $longPollingTimeout, MessageQueue $messageQueue, array $metadata): ServerStreamingCall
    {
        $requset = new ReceiveMessageRequest();
        // $requset->setAttemptId();
        $requset->setAutoRenew(false);
        $requset->setBatchSize($batchSize);
        $requset->setFilterExpression($filterExpression);
        $requset->setGroup($group);
        $requset->setInvisibleDuration($invisibleDuration);
        $requset->setLongPollingTimeout($longPollingTimeout);
        $requset->setMessageQueue($messageQueue);

        return $this->client->ReceiveMessage($requset, $metadata);
    }

    /**
     * @param AckMessageEntry[] $ackMessageEntry
     */
    public function ackMessage(array $ackMessageEntry, Resource $group, Resource $topic, array $metadata): void
    {
        $request = new AckMessageRequest();
        $request->setEntries($ackMessageEntry);
        $request->setGroup($group);
        $request->setTopic($topic);

        /* @var \Apache\Rocketmq\V2\AckMessageResponse */
        [$response] = $this->client->AckMessage($request, $metadata);

        $this->assertResponseOk($response->getStatus(), 'AckMessage');
    }
}
