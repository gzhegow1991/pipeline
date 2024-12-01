<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoSkipFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null, $state = null)
    {
        echo __METHOD__ . PHP_EOL;

        return null;
    }
}
