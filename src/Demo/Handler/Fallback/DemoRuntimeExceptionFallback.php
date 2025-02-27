<?php

namespace Gzhegow\Pipeline\Demo\Handler\Fallback;


class DemoRuntimeExceptionFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        if (! is_a($e, \RuntimeException::class)) return null;

        return __METHOD__ . ' result.';
    }
}
