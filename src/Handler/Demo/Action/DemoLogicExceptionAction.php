<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

use Gzhegow\Pipeline\Exception\LogicException;


class DemoLogicExceptionAction
{
    public function __invoke($result = null, $input = null, $context = null) // : mixed
    {
        var_dump(__METHOD__);

        throw new LogicException('Hello, World!');
    }
}
