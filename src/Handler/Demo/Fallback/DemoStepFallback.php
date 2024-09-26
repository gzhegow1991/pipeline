<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoStepFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        var_dump(__METHOD__);

        return null;
    }
}
