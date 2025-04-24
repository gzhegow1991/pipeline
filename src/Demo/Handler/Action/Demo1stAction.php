<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

class Demo1stAction
{
    public function __invoke($input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
