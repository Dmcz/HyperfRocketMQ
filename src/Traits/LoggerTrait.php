<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Traits;

trait LoggerTrait
{
    protected function debug(string $content, mixed ...$args): void
    {
        if (! $this->logger) {
            return;
        }

        $content = $this->warpLog($content, ...$args);
        $this->logger->debug($content);
    }

    protected function warning(string $content): void
    {
        $content = $this->warpLog($content);
        $this->logger?->warning($content) ?? print $content . "\n";
    }

    private function warpLog(string $content, mixed ...$args)
    {
        $content = sprintf($content, ...$args);

        return sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), __CLASS__, $content);
    }
}
