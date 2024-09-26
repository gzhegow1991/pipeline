<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;


class Demo2ndAction
{
    public function __invoke($result = null, $input = null, $context = null) // : mixed
    {
        var_dump(__METHOD__);

        return __METHOD__ . ' result.';
    }
}
