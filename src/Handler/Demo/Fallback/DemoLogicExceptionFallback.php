<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoLogicExceptionFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        if (! is_a($e, \LogicException::class)) return null;

        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
