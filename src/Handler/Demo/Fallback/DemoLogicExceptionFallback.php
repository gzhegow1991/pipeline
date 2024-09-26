<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoLogicExceptionFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        if (! is_a($e, \LogicException::class)) return null;

        var_dump(__METHOD__);

        return __METHOD__ . ' result.';
    }
}
