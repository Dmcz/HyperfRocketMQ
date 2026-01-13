<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\AckMessageEntry;
use Apache\Rocketmq\V2\Address;
use Apache\Rocketmq\V2\Endpoints;
use Apache\Rocketmq\V2\FilterExpression;
use Apache\Rocketmq\V2\Message;
use Apache\Rocketmq\V2\MessageQueue;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\SendResultEntry;
use Dmcz\HyperfRocketmq\Exception\InvalidArgumentException;
use Dmcz\HyperfRocketmq\Stub\MessagingServiceClient;
use Google\Protobuf\Duration;
use Hyperf\Coroutine\Channel\Pool;
use Hyperf\GrpcClient\GrpcClient;
use Hyperf\GrpcClient\ServerStreamingCall;

class ConnectionManager
{
    protected array $connections = [];

    public function __construct(
        public readonly MetadataFactory $metadataFactory,
    ) {
    }

    /**
     * @return MessageQueue[]
     */
    public function queryRoute(Endpoints $endpoints, Resource $topic): array
    {
        return $this->get($endpoints)->queryRoute($topic, $endpoints, $this->metadataFactory->create());
    }

    public function telemetry(Endpoints $endpoints): Telemetry
    {
        return $this->get($endpoints)->telemetry($this->metadataFactory->create());
    }

    public function heartBeat(Endpoints $endpoints, int $clientType, ?Resource $group): void
    {
        $this->get($endpoints)->heartBeat($clientType, $group, $this->metadataFactory->create());
    }

    /**
     * @param Message[] $messages
     * @return SendResultEntry[]
     */
    public function sendMessage(Endpoints $endpoints, array $messages): array
    {
        return $this->get($endpoints)->sendMessage($messages, $this->metadataFactory->create());
    }

    public function receiveMessage(Endpoints $endpoints, int $batchSize, FilterExpression $filterExpression, Resource $group, Duration $invisibleDuration, Duration $longPollingTimeout, MessageQueue $messageQueue, int $deadline): ServerStreamingCall
    {
        $metadata = $this->metadataFactory->create();

        // TODO 待考量
        $metadata['grpc-timeout'] = $deadline . 'S';
        return $this->get($endpoints)->receiveMessage($batchSize, $filterExpression, $group, $invisibleDuration, $longPollingTimeout, $messageQueue, $metadata);
    }

    /**
     * @param AckMessageEntry[] $ackMessageEntry
     */
    public function ackMessage(Endpoints $endpoints, array $ackMessageEntry, Resource $group, Resource $topic): void
    {
        $this->get($endpoints)->ackMessage($ackMessageEntry, $group, $topic, $this->metadataFactory->create());
    }

    public function get(Endpoints $endpoints): Connection
    {
        $addr = $this->selectAddress($endpoints);

        $host = $addr->getHost();
        $port = $addr->getPort();
        $hostname = $host . ':' . $port;

        if (isset($this->connections[$hostname])) {
            return $this->connections[$hostname];
        }

        return $this->connections[$hostname] = $this->create($hostname);
    }

    public function create(string $hostname): Connection
    {
        $grpcClient = new GrpcClient(new Pool());
        $grpcClient->set($hostname, [
            // TODO
            // 'timeout' => 0.5,//总超时，包括连接、发送、接收所有超时
            // 'connect_timeout' => 1.0,//连接超时，会覆盖第一个总的 timeout
            // 'write_timeout' => 10.0,//发送超时，会覆盖第一个总的 timeout
            'read_timeout' => -1, // 接收超时，会覆盖第一个总的 timeout
        ]);

        $client = new MessagingServiceClient($hostname, [
            'client' => $grpcClient,
        ]);

        return new Connection(
            client: $client,
        );
    }

    // TODO 暂时仅取第一个，后续实现切换和负载均衡
    private function selectAddress(Endpoints $endpoints): Address
    {
        $addresses = $endpoints->getAddresses();
        if ($addresses === null || $addresses->count() <= 0) {
            throw new InvalidArgumentException('endpoints addresses is empty.');
        }

        return $addresses[0];
    }
}
