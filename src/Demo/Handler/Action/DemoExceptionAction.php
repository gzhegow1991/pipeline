<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

use Gzhegow\Pipeline\Exception\Exception;


class DemoExceptionAction
{
    /**
     * @throws Exception
     */
    public function __invoke($input = null, $context = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        throw new Exception('Hello, World!');
    }
}
