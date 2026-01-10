<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Exception;

use RuntimeException;

class ResponseError extends RuntimeException
{
    public function __construct(int $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
