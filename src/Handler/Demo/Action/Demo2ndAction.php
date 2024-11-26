<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;


class Demo2ndAction
{
    public function __invoke($input = null, $context = null, $inputOriginal = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
