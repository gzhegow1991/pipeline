<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;


interface PipelineFacadeInterface
{
    public function new() : PipelineChain;

    public function middleware($from) : MiddlewareChain;


    /**
     * @throws PipelineException
     */
    public function run($pipeline, $input = null, $context = null);
}
