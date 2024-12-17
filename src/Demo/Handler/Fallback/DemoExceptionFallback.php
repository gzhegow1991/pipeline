<?php

namespace Gzhegow\Pipeline\Demo\Handler\Fallback;


class DemoExceptionFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null, $state = null)
    {
        if (! is_a($e, \Exception::class)) return null;

        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
