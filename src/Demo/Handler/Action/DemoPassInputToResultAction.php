<?php

namespace Gzhegow\Pipeline\Demo\Handler\Action;

class DemoPassInputToResultAction
{
    public function __invoke($input = null, $context = null) // : mixed
    {
        echo __METHOD__ . PHP_EOL;

        return $input;
    }
}
