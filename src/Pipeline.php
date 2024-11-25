<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;
use Gzhegow\Pipeline\Exception\Exception\PipelineException;


class Pipeline implements PipelineInterface
{
    const LIST_PIPE = [
        Pipe::TYPE_PIPELINE   => 'pipelineList',
        Pipe::TYPE_MIDDLEWARE => 'middlewareList',
        Pipe::TYPE_ACTION     => 'actionList',
        Pipe::TYPE_FALLBACK   => 'fallbackList',
    ];


    /**
     * @var PipelineProcessorInterface
     */
    protected $processor;

    /**
     * @var int
     */
    protected $lastPipeId = -1;
    /**
     * @var array<int, Pipe>
     */
    protected $pipeList = [];

    /**
     * @var bool
     */
    protected $hasMiddlewares = false;

    /**
     * @var int
     */
    protected $runtimePipeId = -1;
    /**
     * @var Pipe
     */
    protected $runtimePipeCurrentPipeline;
    /**
     * @var Pipe
     */
    protected $runtimePipeCurrentMiddleware;
    /**
     * @var Pipe
     */
    protected $runtimePipeCurrentAction;
    /**
     * @var Pipe
     */
    protected $runtimePipeCurrentFallback;

    /**
     * @var mixed
     */
    protected $runtimeInputOriginal;

    /**
     * @var \Throwable[]
     */
    protected $runtimeThrowables = [];


    public function __construct(PipelineProcessorInterface $processor)
    {
        $this->processor = $processor;
    }


    public function pipelines(array $pipelines) // : static
    {
        foreach ( $pipelines as $pipeline ) {
            $this->pipeline($pipeline);
        }

        return $this;
    }

    public function pipeline($pipeline) // : static
    {
        $this->addPipeline($pipeline);

        return $this;
    }

    public function addPipeline(self $pipeline) : int
    {
        $id = ++$this->lastPipeId;

        $pipelineChild = clone $pipeline;
        $pipelineChild->doReset();

        $pipe = new Pipe(Pipe::TYPE_PIPELINE, $pipelineChild);

        $this->pipeList[ $id ] = $pipe;

        return $id;
    }


    public function middlewares(array $middlewares) // : static
    {
        foreach ( $middlewares as $middleware ) {
            $this->middleware($middleware);
        }

        return $this;
    }

    public function middleware($middleware) // : static
    {
        $genericMiddleware = GenericMiddleware::from($middleware);

        $this->addMiddleware($genericMiddleware);

        return $this;
    }

    public function addMiddleware(GenericMiddleware $middleware) : int
    {
        $id = ++$this->lastPipeId;

        $pipe = new Pipe(Pipe::TYPE_MIDDLEWARE, null, $middleware);

        $this->pipeList[ $id ] = $pipe;

        $this->hasMiddlewares = true;

        return $id;
    }


    public function actions(array $actions) // : static
    {
        foreach ( $actions as $action ) {
            $this->action($action);
        }

        return $this;
    }

    public function action($action) // : static
    {
        $genericAction = GenericAction::from($action);

        $this->addAction($genericAction);

        return $this;
    }

    public function addAction(GenericAction $action) : int
    {
        $id = ++$this->lastPipeId;

        $pipe = new Pipe(Pipe::TYPE_ACTION, null, $action);

        $this->pipeList[ $id ] = $pipe;

        return $id;
    }


    public function fallbacks(array $fallbacks) // : static
    {
        foreach ( $fallbacks as $fallback ) {
            $this->addFallback($fallback);
        }

        return $this;
    }

    public function fallback($fallback) // : static
    {
        $genericFallback = GenericFallback::from($fallback);

        $this->addFallback($genericFallback);

        return $this;
    }

    public function addFallback(GenericFallback $fallback) : int
    {
        $id = ++$this->lastPipeId;

        $pipe = new Pipe(Pipe::TYPE_FALLBACK, null, $fallback);

        $this->pipeList[ $id ] = $pipe;

        return $id;
    }


    /**
     * @throws \Throwable
     */
    public function run($input = null, $context = null) // : mixed
    {
        $outputArray = $this->doRun($input, $context);

        $output = null;
        if (count($outputArray)) {
            [ $output ] = $outputArray;
        }

        return $output;
    }

    public function next($input = null, $context = null) // : mixed
    {
        $outputArray = $this->doNext($input, $context);

        $output = null;
        if (count($outputArray)) {
            [ $output ] = $outputArray;
        }

        return $output;
    }


    /**
     * @throws \Throwable
     */
    protected function doRun($input = null, $context = null) : array
    {
        $this->doReset();

        $this->runtimeInputOriginal = $input;

        $output = $input;
        $outputArray = [];
        while ( $pipe = $this->selectNextPipe() ) {
            $outputArray = $this->doPipe($pipe, $output, $context);

            if (count($outputArray)) {
                [ $output ] = $outputArray;
            }
        }

        if ($this->runtimeThrowables) {
            $e = new PipelineException(
                'Unhandled exception occured during processing pipeline', -1
            );

            foreach ( $this->runtimeThrowables as $ee ) {
                $e->addPrevious($ee);
            }

            throw $e;
        }

        return $outputArray;
    }

    protected function doReset() : void
    {
        $this->runtimePipeId = -1;
        $this->runtimePipeCurrentPipeline = null;
        $this->runtimePipeCurrentMiddleware = null;
        $this->runtimePipeCurrentAction = null;
        $this->runtimePipeCurrentFallback = null;

        $this->runtimeInputOriginal = null;

        $this->runtimeThrowables = [];
    }

    protected function doNext($input = null, $context = null) : array
    {
        if (null === ($pipe = $this->selectNextPipe())) {
            return [];
        }

        $outputArray = $this->doPipe($pipe, $input, $context);

        return $outputArray;
    }


    protected function selectNextPipe() : ?Pipe
    {
        $pipe = null
            ?? $this->selectNextPipeChildInstance()
            ?? $this->selectNextPipeCurrentInstance();

        return $pipe;
    }

    protected function selectNextPipeChildInstance() : ?Pipe
    {
        if (! $this->runtimePipeCurrentPipeline) {
            return null;
        }

        $pipe = $this->runtimePipeCurrentPipeline
            ->getPipeline()
            ->selectNextPipe()
        ;

        if (null === $pipe) {
            $this->runtimePipeCurrentPipeline = null;

            return null;
        }

        return $pipe;
    }

    protected function selectNextPipeCurrentInstance() : ?Pipe
    {
        if ($this->runtimePipeId === $this->lastPipeId) {
            return null;
        }

        $this->runtimePipeId++;

        $pipeRuntime = $this->getPipe($this->runtimePipeId);

        $pipe = null
            ?? $this->selectNextPipeParentPipelineByTypePipeline($pipeRuntime)
            ?? $this->selectNextPipeParentPipelineByTypeMiddleware($pipeRuntime)
            ?? $this->selectNextPipeParentPipelineByTypeAction($pipeRuntime)
            ?? $this->selectNextPipeParentPipelineByTypeFallback($pipeRuntime);

        return $pipe;
    }

    protected function selectNextPipeParentPipelineByTypePipeline(Pipe $pipe) : ?Pipe
    {
        if ($pipe->getType() !== Pipe::TYPE_PIPELINE) {
            return null;
        }

        $this->runtimePipeCurrentPipeline = $pipe;

        $pipeChild = $pipe
            ->getPipeline()
            ->selectNextPipe()
        ;

        if (null === $pipeChild) {
            $this->runtimePipeCurrentPipeline = null;

            return null;
        }

        return $pipeChild;
    }

    protected function selectNextPipeParentPipelineByTypeMiddleware(Pipe $pipe) : ?Pipe
    {
        if ($pipe->getType() !== Pipe::TYPE_MIDDLEWARE) {
            return null;
        }

        return $pipe;
    }

    protected function selectNextPipeParentPipelineByTypeAction(Pipe $pipe) : ?Pipe
    {
        if ($pipe->getType() !== Pipe::TYPE_ACTION) {
            return null;
        }

        $isPipeAvailable = (false
            || ! $this->hasMiddlewares
            || $this->runtimePipeCurrentMiddleware
        );

        if (! $isPipeAvailable) {
            return null;
        }

        return $pipe;
    }

    protected function selectNextPipeParentPipelineByTypeFallback(Pipe $pipe) : ?Pipe
    {
        if ($pipe->getType() !== Pipe::TYPE_FALLBACK) {
            return null;
        }

        $isPipeAvailable = (false
            || ! $this->hasMiddlewares
            || $this->runtimePipeCurrentMiddleware
        );

        if (! $isPipeAvailable) {
            return null;
        }

        return $pipe;
    }


    protected function doPipe(Pipe $pipe, $input = null, $context = null) : array
    {
        $resultArray = null
            ?? $this->doPipeByTypeMiddleware($pipe, $input, $context)
            ?? $this->doPipeByTypeAction($pipe, $input, $context)
            ?? $this->doPipeByTypeFallback($pipe, $input, $context);

        if (null === $resultArray) {
            throw new RuntimeException(
                'Unknown `pipeType`: ' . $pipe->getType()
            );
        }

        return $resultArray;
    }

    protected function doPipeByTypeMiddleware(Pipe $pipe, $input = null, $context = null) : ?array
    {
        if ($pipe->getType() !== Pipe::TYPE_MIDDLEWARE) {
            return null;
        }

        if (count($this->runtimeThrowables)) {
            return [];
        }

        $this->runtimePipeCurrentMiddleware = $pipe;

        $method = [ $this->processor, 'callMiddleware' ];
        $methodArgs = [
            0 => $pipe->getHandler(),
            1 => $this,
            2 => $input,
            3 => $context,
            4 => $this->runtimeInputOriginal,
        ];

        $resultArray = [];

        try {
            $resultArray = call_user_func_array(
                $method,
                $methodArgs
            );
        }
        catch ( \Throwable $e ) {
            $this->runtimeThrowables[] = $e;
        }

        $this->runtimePipeCurrentMiddleware = null;

        return $resultArray;
    }

    protected function doPipeByTypeAction(Pipe $pipe, $input = null, $context = null) : ?array
    {
        if ($pipe->getType() !== Pipe::TYPE_ACTION) {
            return null;
        }

        if (count($this->runtimeThrowables)) {
            return [];
        }

        $this->runtimePipeCurrentAction = $pipe;

        $method = [ $this->processor, 'callAction' ];
        $methodArgs = [
            0 => $pipe->getHandler(),
            1 => $this,
            2 => $input,
            3 => $context,
            4 => $this->runtimeInputOriginal,
        ];

        $resultArray = [];

        try {
            $resultArray = call_user_func_array(
                $method,
                $methodArgs
            );
        }
        catch ( \Throwable $e ) {
            $this->runtimeThrowables[] = $e;
        }

        $this->runtimePipeCurrentAction = null;

        return $resultArray;
    }

    protected function doPipeByTypeFallback(Pipe $pipe, $input = null, $context = null) : ?array
    {
        if ($pipe->getType() !== Pipe::TYPE_FALLBACK) {
            return null;
        }

        if (! count($this->runtimeThrowables)) {
            return [];
        }

        $this->runtimePipeCurrentFallback = $pipe;

        $latestThrowable = end($this->runtimeThrowables);

        $method = [ $this->processor, 'callFallback' ];
        $methodArgs = [
            0 => $pipe->getHandler(),
            1 => $this,
            2 => $latestThrowable,
            3 => $input,
            4 => $context,
            5 => $this->runtimeInputOriginal,
        ];

        $resultArray = [];

        try {
            $resultArray = call_user_func_array(
                $method,
                $methodArgs
            );

            if (count($resultArray)) {
                array_pop($this->runtimeThrowables);
            }
        }
        catch ( \Throwable $e ) {
            $this->runtimeThrowables[] = $e;
        }

        $this->runtimePipeCurrentFallback = null;

        return $resultArray;
    }


    protected function getPipe(int $id) : ?Pipe
    {
        if (! isset($this->pipeList[ $id ])) {
            return null;
        }

        $pipe = $this->pipeList[ $id ];

        return $pipe;
    }
}
