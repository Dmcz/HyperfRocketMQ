<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\Address;
use Apache\Rocketmq\V2\AddressScheme;
use Apache\Rocketmq\V2\Endpoints;
use Dmcz\HyperfRocketmq\Exception\CriticalError;
use Dmcz\HyperfRocketmq\Exception\InvalidArgumentException;
use Stringable;

final class Target implements Stringable
{
    private array $addresses = [];

    private string $canonical = '';

    /**
     * @param array<string,array{host:string,port:int}> $addresses
     */
    public function __construct(
        public readonly TargetScheme $scheme,
        array $addresses = [],
    ) {
        $normalized = [];
        foreach ($addresses as $addr) {
            if (! is_array($addr) || ! isset($addr['host'], $addr['port'])) {
                throw new InvalidArgumentException('addresses element must be array{host:string,port:int}');
            }
            [$host, $port] = $this->validateAndNormalize($addr['host'], (int) $addr['port']);
            $key = $host . ':' . $port;
            $normalized[$key] = ['host' => $host, 'port' => $port];
        }

        if (empty($normalized)) {
            throw new CriticalError('The address is empty.');
        }

        $this->addresses = $normalized;
    }

    public function __toString()
    {
        return $this->canonical();
    }

    public function withAddress(string $host, int $port): self
    {
        [$host, $port] = $this->validateAndNormalize($host, $port);

        $addresses = $this->addresses;
        $key = $host . ':' . $port;
        $addresses[$key] = ['host' => $host, 'port' => $port];

        return new self($this->scheme, $addresses);
    }

    /**
     * @return array{host:string,port:int}[]
     */
    public function addresses(): array
    {
        return array_values($this->addresses);
    }

    /**
     * @return array{host:string,port:int}
     */
    public function first(): array
    {
        return $this->addresses[array_key_first($this->addresses)];
    }

    public function canonical(): string
    {
        if ($this->canonical) {
            return $this->canonical;
        }

        $scheme = $this->scheme->value;
        $parts = [];

        // TODO 考虑一下顺序问题
        foreach ($this->addresses() as $a) {
            $parts[] = $a['host'] . ':' . $a['port'];
        }

        return $this->canonical = $scheme . '://' . implode(';', $parts);
    }

    public function toEndpoints(): Endpoints
    {
        $endpoints = new Endpoints();
        $endpoints->setScheme(match ($this->scheme) {
            TargetScheme::IPv4 => AddressScheme::IPv4,
            TargetScheme::IPv6 => AddressScheme::IPv6,
            TargetScheme::DOMAIN => AddressScheme::DOMAIN_NAME,
        });

        $addresses = [];
        foreach ($this->addresses() as $addr) {
            $address = new Address();
            $address->setHost($addr['host']);
            $address->setPort($addr['port']);
            $addresses[] = $address;
        }

        $endpoints->setAddresses($addresses);

        return $endpoints;
    }

    public static function fromEndpoints(Endpoints $endpoints): static
    {
        $scheme = match ($endpoints->getScheme()) {
            AddressScheme::IPv4 => TargetScheme::IPv4,
            AddressScheme::IPv6 => TargetScheme::IPv6,
            AddressScheme::DOMAIN_NAME => TargetScheme::DOMAIN,
        };

        $addresses = [];
        /* @var Address */
        foreach ($endpoints->getAddresses() as $addr) {
            $addresses[] = ['host' => $addr->getHost(), 'port' => $addr->getPort()];
        }

        return new static($scheme, $addresses);
    }

    public static function parse(string $raw): static
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new InvalidArgumentException('target is empty');
        }

        [$schemeStr, $addressStr] = self::parseScheme($raw);

        $scheme = match ($schemeStr) {
            'dns', 'domain', 'domain_name' => TargetScheme::DOMAIN,
            'ipv4' => TargetScheme::IPv4,
            'ipv6' => TargetScheme::IPv6,
            default => null,
        };

        $addresses = [];

        foreach (self::parseAddressStr($addressStr) as [$addrScheme, $host, $port]) {
            if ($scheme === null) {
                if ($schemeStr === 'ip' && ! in_array($addrScheme, [TargetScheme::IPv4, TargetScheme::IPv6], true)) {
                    throw new InvalidArgumentException('The target address scheme is not ip');
                }

                $scheme = $addrScheme;
            } elseif (
                in_array($addrScheme, [TargetScheme::IPv4, TargetScheme::IPv6], true)
                && $scheme !== $addrScheme
            ) {
                throw new InvalidArgumentException('The target address scheme is inconsistecy.');
            }

            $addresses[] = ['host' => $host, 'port' => $port];
        }

        if ($scheme === null) {
            throw new InvalidArgumentException('no valid addresses in target');
        }

        return new self($scheme, $addresses);
    }

    /**
     * example：
     *  - rest => [null, rest]
     *  - ://rest => ['', rest]
     *  - scheme://rest => [scheme, rest]
     *  - scheme:///rest => [scheme, rest]
     *  - scheme://///////rest => [scheme, rest]
     *  - ip:///1.1.1.1:8081;2.2.2.2:8081
     *  - ipv4:///1.1.1.1:8081;2.2.2.2:8081
     *  - ipv6:///1.1.1.1:8081;2.2.2.2:8081
     *  - domain:///addr1:8081;addr2:8081
     *  - addr1:8081;addr2:8081.
     *
     * @return array{0:?string,1:string} [0: scheme, 1: rest]
     */
    private static function parseScheme(string $raw): array
    {
        $pos = strpos($raw, '://');
        if ($pos === false) {
            return [null, $raw];
        }

        $schemeStr = strtolower(trim(substr($raw, 0, $pos)));
        $rest = substr($raw, $pos + 3); // skip ://

        // 兼容 :///（以及更多 /），统一去掉前导 /
        $rest = ltrim($rest, '/');

        return [$schemeStr, $rest];
    }

    /**
     * @return array{TargetScheme,string,int}[]
     */
    private static function parseAddressStr(string $addressStr): array
    {
        $addrStrArr = array_values(array_filter(
            array_map('trim', explode(';', $addressStr)),
            static fn ($s) => $s !== ''
        ));

        if (! $addrStrArr) {
            throw new InvalidArgumentException('no valid addresses in target');
        }

        $addresses = [];
        foreach ($addrStrArr as $addrStr) {
            $addresses[] = self::parseAddress($addrStr);
        }

        return $addresses;
    }

    /**
     * @return array{TargetScheme,string,int}
     */
    private static function parseAddress(string $address): array
    {
        $address = trim($address);
        if ($address === '') {
            throw new InvalidArgumentException('address is empty');
        }

        // IPv6: [addr]:port
        if (str_starts_with($address, '[')) {
            $rb = strpos($address, ']');
            if ($rb === false) {
                throw new InvalidArgumentException("invalid IPv6 address: {$address}");
            }

            $host = substr($address, 1, $rb - 1);
            $rest = substr($address, $rb + 1);

            if ($host === '' || ! str_starts_with($rest, ':')) {
                throw new InvalidArgumentException("invalid IPv6 address format: {$address}");
            }

            $portStr = substr($rest, 1);
            if ($portStr === '' || ! ctype_digit($portStr)) {
                throw new InvalidArgumentException("invalid port in: {$address}");
            }

            $port = (int) $portStr;
            if ($port < 1 || $port > 65535) {
                throw new InvalidArgumentException("invalid port in: {$address}");
            }

            return [TargetScheme::IPv6, $host, $port];
        }

        // domain/ipv4: host:port
        $pos = strrpos($address, ':');
        if ($pos === false) {
            throw new InvalidArgumentException("missing port in: {$address}");
        }

        $host = trim(substr($address, 0, $pos));
        $portStr = trim(substr($address, $pos + 1));

        if ($host === '' || $portStr === '' || ! ctype_digit($portStr)) {
            throw new InvalidArgumentException("invalid host/port in: {$address}");
        }

        $port = (int) $portStr;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("invalid port in: {$address}");
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return [TargetScheme::IPv4, $host, $port];
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return [TargetScheme::IPv6, $host, $port];
        }

        return [TargetScheme::DOMAIN, $host, $port];
    }

    /**
     * @return array{0:string,1:int} [host, port]
     */
    private function validateAndNormalize(string $host, int $port): array
    {
        $host = trim($host);

        if ($host === '') {
            throw new InvalidArgumentException('host is empty');
        }
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("invalid port: {$port}");
        }

        if ($this->scheme === TargetScheme::IPv4) {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                throw new InvalidArgumentException("scheme IPv4 requires IPv4 address: {$host}");
            }
        } elseif ($this->scheme === TargetScheme::IPv6) {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                throw new InvalidArgumentException("scheme IPv6 requires IPv6 address: {$host}");
            }
        }

        return [$host, $port];
    }
}
