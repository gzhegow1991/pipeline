<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

use Gzhegow\Pipeline\Exception\Exception;


class DemoExceptionAction
{
    /**
     * @throws Exception
     */
    public function __invoke($input = null, $context = null, $state = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        throw new Exception('Hello, World!');
    }
}
