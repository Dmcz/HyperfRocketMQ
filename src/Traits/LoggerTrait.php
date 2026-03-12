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

    protected function debugFn(callable $fn): void
    {
        if (! $this->logger) {
            return;
        }

        $content = $fn();

        if ($content === null || $content === '') {
            return;
        }

        $this->logger->debug($this->warpLog((string) $content));
    }

    protected function warning(string $content, mixed ...$args): void
    {
        $content = $this->warpLog($content, ...$args);
        $this->logger?->warning($content) ?? print $content . "\n";
    }

    private function warpLog(string $content, mixed ...$args): string
    {
        if ($args) {
            $content = sprintf($content, ...$args);
        }

        return sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), static::class, $content);
    }
}