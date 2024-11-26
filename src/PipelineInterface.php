<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;
use Gzhegow\Pipeline\Exception\Exception\PipelineException;


interface PipelineInterface
{
    public function pipelines(array $pipelines);

    public function pipeline($pipeline);

    public function addPipeline(PipelineInterface $pipeline) : int;


    public function middlewares(array $middlewares);

    public function middleware($middleware);

    public function addMiddleware(GenericMiddleware $middleware) : int;


    public function actions(array $actions);

    public function action($action);

    public function addAction(GenericAction $action) : int;


    public function fallbacks(array $fallbacks);

    public function fallback($fallback);

    public function addFallback(GenericFallback $fallback) : int;


    /**
     * @throws PipelineException
     */
    public function run($input = null, $context = null);

    public function next($input = null, $context = null);
}
