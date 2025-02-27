<?php

namespace Gzhegow\Pipeline\Demo\Handler\Fallback;


class DemoLogicExceptionFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        if (! is_a($e, \LogicException::class)) return null;

        return __METHOD__ . ' result.';
    }
}
