<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

use Gzhegow\Pipeline\Exception\LogicException;


class DemoLogicExceptionAction
{
    public function __invoke($input = null, $context = null, $inputOriginal = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        throw new LogicException('Hello, World!');
    }
}
