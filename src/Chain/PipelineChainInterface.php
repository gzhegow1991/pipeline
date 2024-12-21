<?php

namespace Gzhegow\Pipeline\Chain;

use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


interface PipelineChainInterface
{
    /**
     * @return PipelinePipe<PipelineChainInterface|GenericHandler>[]
     */
    public function getPipes() : array;


    /**
     * @return static
     */
    public function pipeline(PipelineChain $from); // : static

    public function startPipeline() : PipelineChain;

    public function endPipeline() : PipelineChainInterface;


    /**
     * @return static
     */
    public function middleware(MiddlewareChain $from); // : static

    public function startMiddleware($from) : MiddlewareChain;

    public function endMiddleware() : PipelineChainInterface;


    /**
     * @return static
     */
    public function action($from); // : static

    /**
     * @return static
     */
    public function fallback($from); // : static


    /**
     * @return \Throwable[]
     */
    public function getThrowables() : array;

    public function latestThrowable() : ?\Throwable;

    public function popThrowable() : ?\Throwable;

    public function throwable(\Throwable $throwable); // : static
}
