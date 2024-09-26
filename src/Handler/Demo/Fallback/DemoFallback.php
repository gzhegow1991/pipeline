<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        var_dump(__METHOD__);

        return __METHOD__ . ' result.';
    }
}
