<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

enum TargetScheme: string
{
    case IPv4 = 'ipv4';
    case IPv6 = 'ipv6';
    case DOMAIN = 'domian';
}
