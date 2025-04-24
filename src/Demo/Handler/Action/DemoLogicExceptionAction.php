<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

use Gzhegow\Pipeline\Exception\LogicException;


class DemoLogicExceptionAction
{
    public function __invoke($input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        throw new LogicException('Hello, World!');
    }
}
