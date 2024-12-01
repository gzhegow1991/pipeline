<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoRuntimeExceptionFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null, $state = null)
    {
        if (! is_a($e, \RuntimeException::class)) return null;

        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
