<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\Settings;
use Apache\Rocketmq\V2\TelemetryCommand;
use Dmcz\HyperfRocketmq\Traits\ResponseStatusAssertTrait;
use Hyperf\GrpcClient\BidiStreamingCall;

class Telemetry
{
    use ResponseStatusAssertTrait;

    public function __construct(
        protected BidiStreamingCall $call,
    ) {
    }

    public function setSettings(Settings $settings): void
    {
        $cmd = new TelemetryCommand();
        $cmd->setSettings($settings);
        $this->call->push($cmd);
    }

    /**
     * @return TelemetryCommand[]|null
     */
    public function recevie(): ?array
    {
        /**
         * @var TelemetryCommand[] $recv
         * @var int $status
         * @var Response $resp
         */
        [$recv, $status, $resp] = $this->call->recv();

        if($recv === null){
            // TODO Detect gRPC status and consider whether an exception should be thrown,
            // or change gRPC library.
            return null;
        }

        foreach($recv as $message){
            $this->assertResponseOk($message->getStatus(), 'ReceiveTelemetryCommand');
        }

        return $recv;
    }
}
