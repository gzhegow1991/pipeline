<?php

namespace Gzhegow\Pipeline\Handler\Demo\Fallback;


class DemoSkipFallback
{
    public function __invoke(\Throwable $e, $result = null, $input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        return null;
    }
}
