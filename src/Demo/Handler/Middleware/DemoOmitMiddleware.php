<?php

namespace Gzhegow\Pipeline\Demo\Handler\Middleware;

use Gzhegow\Pipeline\Process\PipelineProcessInterface;


class DemoOmitMiddleware
{
    public function __invoke(PipelineProcessInterface $pipeline, $input = null, $context = null, $state = null) // : mixed
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = __METHOD__ . ' result.';

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
