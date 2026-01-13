<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Stub;

use Apache\Rocketmq\V2\AckMessageRequest;
use Apache\Rocketmq\V2\AckMessageResponse;
use Apache\Rocketmq\V2\ChangeInvisibleDurationRequest;
use Apache\Rocketmq\V2\ChangeInvisibleDurationResponse;
use Apache\Rocketmq\V2\EndTransactionRequest;
use Apache\Rocketmq\V2\EndTransactionResponse;
use Apache\Rocketmq\V2\ForwardMessageToDeadLetterQueueRequest;
use Apache\Rocketmq\V2\ForwardMessageToDeadLetterQueueResponse;
use Apache\Rocketmq\V2\GetOffsetRequest;
use Apache\Rocketmq\V2\GetOffsetResponse;
use Apache\Rocketmq\V2\HeartbeatRequest;
use Apache\Rocketmq\V2\HeartbeatResponse;
use Apache\Rocketmq\V2\NotifyClientTerminationRequest;
use Apache\Rocketmq\V2\NotifyClientTerminationResponse;
use Apache\Rocketmq\V2\PullMessageRequest;
use Apache\Rocketmq\V2\QueryAssignmentRequest;
use Apache\Rocketmq\V2\QueryAssignmentResponse;
use Apache\Rocketmq\V2\QueryOffsetRequest;
use Apache\Rocketmq\V2\QueryOffsetResponse;
use Apache\Rocketmq\V2\QueryRouteRequest;
use Apache\Rocketmq\V2\QueryRouteResponse;
use Apache\Rocketmq\V2\RecallMessageRequest;
use Apache\Rocketmq\V2\RecallMessageResponse;
use Apache\Rocketmq\V2\ReceiveMessageRequest;
use Apache\Rocketmq\V2\SendMessageRequest;
use Apache\Rocketmq\V2\UpdateOffsetRequest;
use Apache\Rocketmq\V2\UpdateOffsetResponse;
use Grpc\ServerStreamingCall;
use Grpc\UnaryCall;
use Hyperf\GrpcClient\BaseClient;
use Hyperf\GrpcClient\BidiStreamingCall;

class MessagingServiceClient extends BaseClient
{
    /**
     * Queries the route entries of the requested topic in the perspective of the
     * given endpoints. On success, servers should return a collection of
     * addressable message-queues. Note servers may return customized route
     * entries based on endpoints provided.
     *
     * If the requested topic doesn't exist, returns `NOT_FOUND`.
     * If the specific endpoints is empty, returns `INVALID_ARGUMENT`.
     * @param QueryRouteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return QueryRouteResponse
     */
    public function QueryRoute(
        QueryRouteRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/QueryRoute',
            $argument,
            ['\Apache\Rocketmq\V2\QueryRouteResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Producer or consumer sends HeartbeatRequest to servers periodically to
     * keep-alive. Additionally, it also reports client-side configuration,
     * including topic subscription, load-balancing group name, etc.
     *
     * Returns `OK` if success.
     *
     * If a client specifies a language that is not yet supported by servers,
     * returns `INVALID_ARGUMENT`
     * @param HeartbeatRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return HeartbeatResponse
     */
    public function Heartbeat(
        HeartbeatRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/Heartbeat',
            $argument,
            ['\Apache\Rocketmq\V2\HeartbeatResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Delivers messages to brokers.
     * Clients may further:
     * 1. Refine a message destination to message-queues which fulfills parts of
     * FIFO semantic;
     * 2. Flag a message as transactional, which keeps it invisible to consumers
     * until it commits;
     * 3. Time a message, making it invisible to consumers till specified
     * time-point;
     * 4. And more...
     *
     * Returns message-id or transaction-id with status `OK` on success.
     *
     * If the destination topic doesn't exist, returns `NOT_FOUND`.
     * @param SendMessageRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return array
     */
    public function SendMessage(
        SendMessageRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/SendMessage',
            $argument,
            ['\Apache\Rocketmq\V2\SendMessageResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Queries the assigned route info of a topic for current consumer,
     * the returned assignment result is decided by server-side load balancer.
     *
     * If the corresponding topic doesn't exist, returns `NOT_FOUND`.
     * If the specific endpoints is empty, returns `INVALID_ARGUMENT`.
     * @param QueryAssignmentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<QueryAssignmentResponse>
     */
    public function QueryAssignment(
        QueryAssignmentRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/QueryAssignment',
            $argument,
            ['\Apache\Rocketmq\V2\QueryAssignmentResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Receives messages from the server in batch manner, returns a set of
     * messages if success. The received messages should be acked or redelivered
     * after processed.
     *
     * If the pending concurrent receive requests exceed the quota of the given
     * consumer group, returns `UNAVAILABLE`. If the upstream store server hangs,
     * return `DEADLINE_EXCEEDED` in a timely manner. If the corresponding topic
     * or consumer group doesn't exist, returns `NOT_FOUND`. If there is no new
     * message in the specific topic, returns `OK` with an empty message set.
     * Please note that client may suffer from false empty responses.
     *
     * If failed to receive message from remote, server must return only one
     * `ReceiveMessageResponse` as the reply to the request, whose `Status` indicates
     * the specific reason of failure, otherwise, the reply is considered successful.
     * @param ReceiveMessageRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Hyperf\GrpcClient\ServerStreamingCall
     */
    public function ReceiveMessage(
        ReceiveMessageRequest $argument,
        $metadata = [],
        $options = []
    ) {
        $options['timeout'] = 8 * 1000 * 1000;

        $serverStreamingCall = $this->_serverStreamRequest(
            '/apache.rocketmq.v2.MessagingService/ReceiveMessage',
            ['\Apache\Rocketmq\V2\ReceiveMessageResponse', 'decode'],
            $metadata,
            $options
        );

        $serverStreamingCall->send($argument);
        $serverStreamingCall->end();

        return $serverStreamingCall;
    }

    /**
     * Acknowledges the message associated with the `receipt_handle` or `offset`
     * in the `AckMessageRequest`, it means the message has been successfully
     * processed. Returns `OK` if the message server remove the relevant message
     * successfully.
     *
     * If the given receipt_handle is illegal or out of date, returns
     * `INVALID_ARGUMENT`.
     * @param AckMessageRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<AckMessageResponse>
     */
    public function AckMessage(
        AckMessageRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/AckMessage',
            $argument,
            ['\Apache\Rocketmq\V2\AckMessageResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Forwards one message to dead letter queue if the max delivery attempts is
     * exceeded by this message at client-side, return `OK` if success.
     * @param ForwardMessageToDeadLetterQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<ForwardMessageToDeadLetterQueueResponse>
     */
    public function ForwardMessageToDeadLetterQueue(
        ForwardMessageToDeadLetterQueueRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/ForwardMessageToDeadLetterQueue',
            $argument,
            ['\Apache\Rocketmq\V2\ForwardMessageToDeadLetterQueueResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * PullMessage and ReceiveMessage RPCs serve a similar purpose,
     * which is to attempt to get messages from the server, but with different semantics.
     * @param PullMessageRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return ServerStreamingCall
     */
    public function PullMessage(
        PullMessageRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_serverStreamRequest(
            '/apache.rocketmq.v2.MessagingService/PullMessage',
            $argument,
            ['\Apache\Rocketmq\V2\PullMessageResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Update the consumption progress of the designated queue of the
     * consumer group to the remote.
     * @param UpdateOffsetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<UpdateOffsetResponse>
     */
    public function UpdateOffset(
        UpdateOffsetRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/UpdateOffset',
            $argument,
            ['\Apache\Rocketmq\V2\UpdateOffsetResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Query the consumption progress of the designated queue of the
     * consumer group to the remote.
     * @param GetOffsetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<GetOffsetResponse>
     */
    public function GetOffset(
        GetOffsetRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/GetOffset',
            $argument,
            ['\Apache\Rocketmq\V2\GetOffsetResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Query the offset of the designated queue by the query offset policy.
     * @param QueryOffsetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<QueryOffsetResponse>
     */
    public function QueryOffset(
        QueryOffsetRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/QueryOffset',
            $argument,
            ['\Apache\Rocketmq\V2\QueryOffsetResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Commits or rollback one transactional message.
     * @param EndTransactionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<EndTransactionResponse>
     */
    public function EndTransaction(
        EndTransactionRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/EndTransaction',
            $argument,
            ['\Apache\Rocketmq\V2\EndTransactionResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Once a client starts, it would immediately establishes bi-lateral stream
     * RPCs with brokers, reporting its settings as the initiative command.
     *
     * When servers have need of inspecting client status, they would issue
     * telemetry commands to clients. After executing received instructions,
     * clients shall report command execution results through client-side streams.
     * @param array $metadata metadata
     * @param array $options call options
     * @return BidiStreamingCall
     */
    public function Telemetry($metadata = [], $options = [])
    {
        return $this->_bidiRequest(
            '/apache.rocketmq.v2.MessagingService/Telemetry',
            ['\Apache\Rocketmq\V2\TelemetryCommand', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Notify the server that the client is terminated.
     * @param NotifyClientTerminationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<NotifyClientTerminationResponse>
     */
    public function NotifyClientTermination(
        NotifyClientTerminationRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/NotifyClientTermination',
            $argument,
            ['\Apache\Rocketmq\V2\NotifyClientTerminationResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Once a message is retrieved from consume queue on behalf of the group, it
     * will be kept invisible to other clients of the same group for a period of
     * time. The message is supposed to be processed within the invisible
     * duration. If the client, which is in charge of the invisible message, is
     * not capable of processing the message timely, it may use
     * ChangeInvisibleDuration to lengthen invisible duration.
     * @param ChangeInvisibleDurationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<ChangeInvisibleDurationResponse>
     */
    public function ChangeInvisibleDuration(
        ChangeInvisibleDurationRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/ChangeInvisibleDuration',
            $argument,
            ['\Apache\Rocketmq\V2\ChangeInvisibleDurationResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * Recall a message,
     * for delay message, should recall before delivery time, like the rollback operation of transaction message,
     * for normal message, not supported for now.
     * @param RecallMessageRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return UnaryCall<RecallMessageResponse>
     */
    public function RecallMessage(
        RecallMessageRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/apache.rocketmq.v2.MessagingService/RecallMessage',
            $argument,
            ['\Apache\Rocketmq\V2\RecallMessageResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
