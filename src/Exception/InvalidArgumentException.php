<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
