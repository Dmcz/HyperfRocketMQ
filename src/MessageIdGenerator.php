<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

// Reference: RocketMQ Go client v1 message ID generator (message_id_codec.go).
final class MessageIdGenerator
{
    private const VERSION = '01';

    private const CUSTOM_EPOCH = 1609459200;

    private static ?self $instance = null;

    private string $processFixedHex;

    private int $secondsStartTimestamp;

    private int $secondsSinceCustomEpoch;

    private int $sequence = -1;

    private function __construct()
    {
        $mac = $this->getMacBytesOrRandom();
        $pid = getmypid() & 0xFFFF;

        $fixed = $mac . pack('n', $pid);
        $this->processFixedHex = strtoupper(bin2hex($fixed));

        $this->secondsStartTimestamp = time();
        $this->secondsSinceCustomEpoch = $this->secondsStartTimestamp - self::CUSTOM_EPOCH;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function nextId(): string
    {
        $deltaSeconds = $this->deltaSeconds();
        $this->sequence = ($this->sequence + 1) & 0xFFFFFFFF;

        $suffix = $this->processFixedHex
            . strtoupper(bin2hex(pack('N', $deltaSeconds) . pack('N', $this->sequence)));

        return self::VERSION . $suffix;
    }

    private function deltaSeconds(): int
    {
        return (time() - $this->secondsStartTimestamp) + $this->secondsSinceCustomEpoch;
    }

    private function getMacBytesOrRandom(): string
    {
        $mac = Env::getMac();
        if ($mac !== null) {
            $hex = str_replace(':', '', $mac);
            $bytes = hex2bin($hex);
            if ($bytes !== false) {
                return $bytes;
            }
        }
        return random_bytes(6);
    }
}
