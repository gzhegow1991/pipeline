<?php

namespace Gzhegow\Pipeline\Handler\Demo\Action;


class DemoPassInputToResultAction
{
    public function __invoke($input = null, $context = null, $inputOriginal = null) // : mixed
    {
        var_dump(__METHOD__);

        return $input;
    }
}
