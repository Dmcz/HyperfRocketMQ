<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Swoole\Atomic;
use Apache\Rocketmq\V2\UA;
use Hyperf\Engine\Coroutine;
use Psr\Log\LoggerInterface;
use Google\Protobuf\Duration;
use Apache\Rocketmq\V2\Language;
use Apache\Rocketmq\V2\Endpoints;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\Settings;
use Apache\Rocketmq\V2\Subscription;
use Dmcz\HyperfRocketmq\SessionSettings;
use Dmcz\HyperfRocketmq\Traits\LoggerTrait;

abstract class Session
{
    use LoggerTrait;

    public readonly UA $userAgent;

    protected ConnectionManager $connectionManager;

    protected ?Telemetry $telemetry = null;

    protected Endpoints $defaultEndpoints;

    protected Atomic $ready;

    public function __construct(
        public readonly SessionSettings $settings,
        protected ?LoggerInterface $logger = null
    ) {
        $this->userAgent = new UA();
        $this->userAgent->setHostname($this->settings->hostname ?? (gethostname() ?: ''));
        $this->userAgent->setLanguage(Language::PHP);
        $this->userAgent->setPlatform(trim(php_uname('s') . ' ' . php_uname('m')));
        $this->userAgent->setVersion('dev');

        $this->connectionManager = new ConnectionManager(
            new MetadataFactory(
                clientId: $settings->identity->clientId,
                namespace: $settings->identity->namespace,
            )
        );

        $this->defaultEndpoints = $this->settings->target->toEndpoints();

        $this->ready = new Atomic();
    }

    public function start()
    {
        // $this->connectionManager->queryRoute($this->settings->target, )
        $this->telemetry = $this->connectionManager->telemetry($this->defaultEndpoints);

        $this->syncSetting();
        $this->observe();
        $this->heartBeat();
    }

    public function syncSetting(): void
    {
        $this->telemetry->setSettings($this->generateProtobufSetting($this->defaultEndpoints));
    }

    protected function observe()
    {
        Coroutine::create(function () {
            // TODO 关闭
            while (true) {
                $command = $this->telemetry->recevie();

                $this->debug('received telemetry command "' . $command->getCommand() . '"');

                match ($command->getCommand()) {
                    'settings' => $this->onSettingsCommand($command->getSettings()),
                };
                // var_dump($resp->getSettings()->getUserAgent()->getHostname());
            }
        });
    }

    protected function onSettingsCommand(?Settings $settings): void
    {
        if (empty($settings)) {
            $this->warning('Receivce empty setting.');
            return;
        }

        $this->ready->set(1);

        $this->debug('On setting: %s', $settings->serializeToJsonString());
    }

    protected function heartBeat()
    {
        Coroutine::create(function () {
            $this->ready->wait();

            // TODO 优雅关闭，轮训routers
            while (true) {
                $this->connectionManager->heartBeat(
                    $this->defaultEndpoints,
                    $this->getClientType(),
                    $this->getGroup()
                );
                $this->debug('Heartbeat.');

                // TODO
                sleep(15);
            }
        });
    }

    protected function generateProtobufSetting(Endpoints $endpoints): Settings
    {
        $settings = new Settings();

        $settings->setAccessPoint($endpoints);
        // $settings->setBackoffPolicy();
        $settings->setClientType($this->getClientType());
        // $settings->setMetric();
        // $settings->setPublishing();
        $settings->setRequestTimeout((new Duration())->setSeconds($this->settings->requestTimeout));

        $subscription = $this->getSubscription();
        if (! empty($subscription)) {
            $settings->setSubscription($subscription);
        }

        $settings->setUserAgent($this->userAgent);

        return $settings;
    }

    abstract protected function getClientType(): int;

    abstract protected function getGroup(): ?Resource;

    abstract protected function getSubscription(): ?Subscription;
}
