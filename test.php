<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Dmcz\HyperfRocketmq\SimpleConsumer;

use function Swoole\Coroutine\run;

run(function () {
    $client = new SimpleConsumer();

    $client->start();

    // \Swoole\Coroutine::create(function() use($client) {
    //     $client->receive();
    // });
});
