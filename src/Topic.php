<?php

declare(strict_types=1);

namespace Dmcz\HyperfRocketmq;

use Apache\Rocketmq\V2\FilterExpression;
use Apache\Rocketmq\V2\FilterType;
use Apache\Rocketmq\V2\Resource;

class Topic
{
    public function __construct(
        public readonly string $topic,
        public readonly string $expression = '*',
        public readonly ExpressionType $expressionType = ExpressionType::Tag,
        public readonly string $namespace = '',
    ) {
    }

    public function same(self $other): bool
    {
        return $this->topic == $other->topic && $this->expression == $other->expression && $this->expressionType == $other->expressionType;
    }

    public function topicResource(): Resource
    {
        $topic = new Resource();
        $topic->setName($this->topic);
        $topic->setResourceNamespace($this->namespace);

        return $topic;
    }

    public function filterExpression(): FilterExpression
    {
        $filterExpression = new FilterExpression();
        $filterExpression->setExpression($this->expression);
        $filterExpression->setType(match ($this->expressionType) {
            ExpressionType::Sql => FilterType::SQL,
            ExpressionType::Tag => FilterType::TAG,
        });

        return $filterExpression;
    }
}
