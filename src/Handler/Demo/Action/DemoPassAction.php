<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;


class DemoPassAction
{
    public function __invoke($result = null, $input = null, $context = null) // : mixed
    {
        var_dump(__METHOD__);

        return $result;
    }
}
