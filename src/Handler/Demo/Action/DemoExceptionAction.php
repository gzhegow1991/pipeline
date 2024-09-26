<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

use Gzhegow\Pipeline\Exception\Exception;


class DemoExceptionAction
{
    /**
     * @throws Exception
     */
    public function __invoke($result = null, $input = null, $context = null) // : mixed
    {
        var_dump(__METHOD__);

        throw new Exception('Hello, World!');
    }
}
