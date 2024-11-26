<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoThrowableFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        // if (! is_a($e, \Throwable::class)) return null;

        echo __METHOD__ . PHP_EOL;

        return __METHOD__ . ' result.';
    }
}
