<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

class Demo3rdAction
{
    public function __invoke($input = null, $context = null, $state = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
