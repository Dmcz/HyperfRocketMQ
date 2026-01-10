<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq\Traits;

use Apache\Rocketmq\V2\Code;
use Apache\Rocketmq\V2\Status;
use Dmcz\HyperfRocketmq\Exception\ResponseError;

trait ResponseStatusAssertTrait
{
    protected function assertResponseOk(Status $status, string $context = ''): void
    {
        $code = $status->getCode();

        if ($code === Code::OK) {
            return;
        }

        $message = $status->getMessage();
        if ($context !== '') {
            $message = $context . ': ' . $message;
        }

        throw new ResponseError($code, $message);
    }
}
