<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

final class Env
{
    public static string $version = 'dev';

    private static ?string $host = null;

    private static ?string $platform = null;

    private static ?string $mac = null;

    public static function setHost(string $host): void
    {
        self::$host = $host;
    }

    public static function getHost(): string
    {
        if (self::$host === null) {
            self::$host = gethostname() ?: '';
        }

        return self::$host;
    }

    public static function setPlatform(string $platform)
    {
        self::$platform = $platform;
    }

    public static function getPlatform(): string
    {
        if (self::$platform === null) {
            self::$platform = trim(php_uname('s') . ' ' . php_uname('m'));
        }

        return self::$platform;
    }

    public static function setMac(string $mac): void
    {
        self::$mac = self::normalizeMac($mac);
    }

    public static function getMac(): ?string
    {
        if (self::$mac === null) {
            self::$mac = self::detectMac();
        }

        return self::$mac;
    }

    private static function normalizeMac(string $mac): string
    {
        $mac = trim($mac);
        if ($mac === '') {
            return '';
        }
        $mac = strtolower($mac);
        $hex = preg_replace('/[^0-9a-f]/', '', $mac);
        if ($hex === null || $hex == '' || strlen($hex) != 12 || ! ctype_xdigit($hex)) {
            return '';
        }

        return implode(':', str_split($hex, 2));
    }

    private static function detectMac(): ?string
    {
        $mac = self::readMacFromSys();
        if ($mac !== null) {
            return $mac;
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $mac = self::readMacFromIfconfig();
            if ($mac !== null) {
                return $mac;
            }
        }

        return null;
    }

    private static function readMacFromSys(): ?string
    {
        $paths = glob('/sys/class/net/*/address');
        if ($paths === false) {
            return null;
        }
        foreach ($paths as $path) {
            $addr = @file_get_contents($path);
            if ($addr === false) {
                continue;
            }
            $addr = trim($addr);
            if ($addr === '' || $addr === '00:00:00:00:00:00') {
                continue;
            }
            $normalized = self::normalizeMac($addr);
            if ($normalized !== null) {
                return $normalized;
            }
        }
        return null;
    }

    private static function readMacFromIfconfig(): ?string
    {
        $output = shell_exec('/sbin/ifconfig -a 2>/dev/null');
        if (! is_string($output) || $output === '') {
            return null;
        }
        if (preg_match('/\bether\s+([0-9a-f:]{17})\b/i', $output, $matches) !== 1) {
            return null;
        }
        return self::normalizeMac($matches[1]);
    }
}
