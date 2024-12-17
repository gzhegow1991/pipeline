<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

use Gzhegow\Pipeline\Exception\RuntimeException;


class DemoRuntimeExceptionAction
{
    public function __invoke($input = null, $context = null, $state = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        throw new RuntimeException('Hello, World!');
    }
}
