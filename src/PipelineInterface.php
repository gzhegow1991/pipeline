<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;
use Gzhegow\Pipeline\Exception\Exception\PipelineException;


interface PipelineInterface
{
    /**
     * @return static
     */
    public function pipelines(array $pipelines); // : static

    /**
     * @return static
     */
    public function pipeline($pipeline); // : static

    public function addPipeline(PipelineInterface $pipeline) : int;


    /**
     * @return static
     */
    public function middlewares(array $middlewares); // : static

    /**
     * @return static
     */
    public function middleware($middleware); // : static

    public function addMiddleware(GenericMiddleware $middleware) : int;


    /**
     * @return static
     */
    public function actions(array $actions); // : static

    /**
     * @return static
     */
    public function action($action); // : static

    public function addAction(GenericAction $action) : int;


    /**
     * @return static
     */
    public function throwables(array $throwables); // : static

    /**
     * @return static
     */
    public function throwable($throwable); // : static

    public function addThrowable(\Throwable $throwable) : int;


    /**
     * @return static
     */
    public function fallbacks(array $fallbacks); // : static

    /**
     * @return static
     */
    public function fallback($fallback); // : static

    public function addFallback(GenericFallback $fallback) : int;


    public function getState() : \stdClass;


    /**
     * @return static
     */
    public function reset(); // : static

    /**
     * @throws PipelineException
     */
    public function run($input = null, $context = null); // : mixed

    public function next($input = null, $context = null); // : mixed
}
