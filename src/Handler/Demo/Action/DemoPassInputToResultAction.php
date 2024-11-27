<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;

class DemoPassInputToResultAction
{
    public function __invoke($input = null, $context = null, $state = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        return $input;
    }
}
