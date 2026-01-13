<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Dmcz\HyperfRocketmq\Producer;
use Dmcz\HyperfRocketmq\PublishingMessage;
use Dmcz\HyperfRocketmq\Target;
use Dmcz\HyperfRocketmq\Topic;

use function Swoole\Coroutine\run;

run(function () {
    $client = new Producer(
        Target::param('rmqproxy'),
    );

    $client->start();

    $client->sendMessage([
        PublishingMessage::normal(new Topic('test_topic'), 'test1'),
    ]);

    var_dump(123);

    // \Swoole\Coroutine::create(function() use($client) {
    //     $client->receive();
    // });
});
