<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Core\Chain\PipelineChain;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;


interface PipelineInterface
{
    public function pipeline() : PipelineChain;

    public function middleware($from) : MiddlewareChain;


    /**
     * @throws PipelineException
     */
    public function run($pipeline, $input = null, $context = null);
}
