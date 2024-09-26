<?php

namespace Gzhegow\Pipeline\Handler\Demo\Middleware;

use Gzhegow\Pipeline\PipelineInterface;


class Demo1stMiddleware
{
    public function __invoke(PipelineInterface $pipeline, $result = null, $input = null, $context = null) // : mixed
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = $pipeline->next($input, $context);

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
