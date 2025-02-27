<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

class Demo3rdAction
{
    public function __invoke($input = null, $context = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
