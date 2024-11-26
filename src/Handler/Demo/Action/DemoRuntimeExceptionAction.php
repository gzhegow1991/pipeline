<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

use Gzhegow\Pipeline\Exception\RuntimeException;


class DemoRuntimeExceptionAction
{
    public function __invoke($input = null, $context = null, $inputOriginal = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        throw new RuntimeException('Hello, World!');
    }
}
