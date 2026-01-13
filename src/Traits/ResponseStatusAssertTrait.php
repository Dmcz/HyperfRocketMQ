<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Traits;

use Apache\Rocketmq\V2\Code;
use Apache\Rocketmq\V2\Status;
use Dmcz\HyperfRocketmq\Exception\ResponseError;
use Google\Protobuf\Internal\Message;
use Swoole\Http2\Response;

trait ResponseStatusAssertTrait
{
    protected function extractReply(array $response, string $context = ''): Message
    {
        /**
         * @var Message $reply
         * @var int $status
         * @var Response $resp
         */
        [$reply, $status, $resp] = $response;

        if (! $reply instanceof Message) {
            $grpcCode = $status;
            $grpcMessage = is_string($reply) ? $reply : null;

            if ($resp instanceof Response) {
                $grpcCode = $resp->headers['grpc-status'] ?? $grpcCode;
                $grpcMessage = $resp->headers['grpc-message'] ?? $grpcMessage;
            }

            if ($grpcMessage === null || $grpcMessage === '') {
                $grpcMessage = 'Empty reply';
            }

            if ($context !== '') {
                $grpcMessage = $context . ': ' . $grpcMessage;
            }

            $code = is_numeric($grpcCode) ? (int) $grpcCode : 0;
            throw new ResponseError($code, $grpcMessage);
        }

        return $reply;
    }

    protected function assertResponseOk(Status $status, string $context = ''): void
    {
        $code = $status->getCode();

        if ($code === Code::OK) {
            return;
        }

        $message = $status->getMessage();
        if ($context !== '') {
            $message = $context . ': ' . $message;
        }

        throw new ResponseError($code, $message);
    }
}
