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

    public function recevie(): TelemetryCommand
    {
        /* @var TelemetryCommand */
        [$resp] = $this->call->recv();

        $this->assertResponseOk($resp->getStatus(), 'ReceiveTelemetryCommand');

        return $resp;
    }
}
