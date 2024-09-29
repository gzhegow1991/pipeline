<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Handler\Action\GenericAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;


class Pipeline implements PipelineInterface
{
    /**
     * @var PipelineProcessorInterface
     */
    protected $processor;

    /**
     * @var int
     */
    protected $middlewareIdLast = 0;
    /**
     * @var GenericMiddleware[]
     */
    protected $middlewareList = [];

    /**
     * @var int
     */
    protected $actionIdLast = 0;
    /**
     * @var GenericAction[]
     */
    protected $actionList = [];

    /**
     * @var int
     */
    protected $fallbackIdLast = 0;
    /**
     * @var GenericFallback[]
     */
    protected $fallbackList = [];

    /**
     * @var GenericMiddleware
     */
    protected $middleware;
    /**
     * @var GenericAction
     */
    protected $action;
    /**
     * @var GenericFallback
     */
    protected $fallback;
    /**
     * @var \Throwable
     */
    protected $throwable;


    public function __construct(PipelineProcessorInterface $processor)
    {
        $this->processor = $processor;
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

    public function addMiddlewares(array $middlewares) : array
    {
        $ids = [];

        foreach ( $middlewares as $middleware ) {
            $ids[] = $this->addMiddleware($middleware);
        }

        return $ids;
    }

    public function addMiddleware(GenericMiddleware $middleware) : int
    {
        $id = ++$this->middlewareIdLast;

        $this->middlewareList[ $id ] = $middleware;

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

    public function addActions(array $actions) : array
    {
        $ids = [];

        foreach ( $actions as $action ) {
            $ids[] = $this->addAction($action);
        }

        return $ids;
    }

    public function addAction(GenericAction $action) : int
    {
        $id = ++$this->actionIdLast;

        $this->actionList[ $id ] = $action;

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

    public function addFallbacks(array $fallbacks) : array
    {
        $ids = [];

        foreach ( $fallbacks as $fallback ) {
            $ids[] = $this->addFallback($fallback);
        }

        return $ids;
    }

    public function addFallback(GenericFallback $fallback) : int
    {
        $id = ++$this->fallbackIdLast;

        $this->fallbackList[ $id ] = $fallback;

        return $id;
    }


    /**
     * @throws \Throwable
     */
    public function run($input = null, $context = null, $result = null) // : mixed
    {
        reset($this->middlewareList);
        reset($this->actionList);
        reset($this->fallbackList);

        $outputArray = [];

        if (count($this->middlewareList)) {
            $outputArray = $this->doCurrentMiddleware($result, $input, $context);

        } elseif (count($this->actionList)) {
            $outputArray = $this->doActions($result, $input, $context);
        }

        $output = null;
        if (count($outputArray)) {
            [ $output ] = $outputArray;
        }

        if ($this->throwable) {
            throw $this->throwable;
        }

        return $output;
    }

    public function next($result = null, $input = null, $context = null) // : mixed
    {
        $resultArray = [];

        if (null !== $result) {
            $resultArray = [ $result ];
        }

        if ($this->middlewareIdLast > key($this->middlewareList)) {
            $resultArray = $this->doNextMiddleware($result, $input, $context);

        } elseif (count($this->actionList)) {
            $resultArray = $this->doActions($result, $input, $context);
        }

        if (count($resultArray)) {
            [ $result ] = $resultArray;
        }

        return $result;
    }


    protected function getCurrentMiddleware() : ?GenericMiddleware
    {
        $this->middleware = null;

        if (null !== ($id = key($this->middlewareList))) {
            return $this->middleware = $this->middlewareList[ $id ];
        }

        return null;
    }

    protected function getCurrentAction() : ?GenericAction
    {
        $this->action = null;

        if (null !== ($id = key($this->actionList))) {
            return $this->action = $this->actionList[ $id ];
        }

        return null;
    }

    protected function getCurrentFallback() : ?GenericFallback
    {
        $this->fallback = null;

        if (null !== ($id = key($this->fallbackList))) {
            return $this->fallback = $this->fallbackList[ $id ];
        }

        return null;
    }


    /**
     * @return array{ 0?: mixed }
     */
    protected function doNextMiddleware($result = null, $input = null, $context = null) : array
    {
        next($this->middlewareList);

        $resultArray = $this->doCurrentMiddleware($result, $input, $context);

        return $resultArray;
    }

    /**
     * @return array{ 0?: mixed }
     */
    protected function doCurrentMiddleware($result = null, $input = null, $context = null) : array
    {
        $middleware = $this->getCurrentMiddleware();

        if (null === $middleware) {
            return [];
        }

        if ($this->throwable) {
            return [];
        }

        try {
            $resultArray = $this->processor->callMiddleware(
                $this->middleware,
                $this,
                $result, $input, $context
            );
        }
        catch ( \Throwable $e ) {
            $this->throwable = $e;

            end($this->middlewareList);
            end($this->actionList);

            $resultArray = $this->doCurrentFallback();
        }

        return $resultArray;
    }


    /**
     * @return array{ 0?: mixed }
     */
    protected function doActions($result = null, $input = null, $context = null) : array
    {
        do {
            $outputArray = $this->doCurrentAction($result, $input, $context);

            if ($this->actionIdLast > key($this->actionList)) {
                if (count($outputArray)) {
                    [ $result ] = $outputArray;
                }
            }

            next($this->actionList);
        } while ( null !== key($this->actionList) );

        return $outputArray;
    }

    /**
     * @return array{ 0?: mixed }
     */
    protected function doNextAction($result = null, $input = null, $context = null) : array
    {
        next($this->actionList);

        $resultArray = $this->doCurrentAction($result, $input, $context);

        return $resultArray;
    }

    /**
     * @return array{ 0?: mixed }
     */
    protected function doCurrentAction($result = null, $input = null, $context = null) : array
    {
        $action = $this->getCurrentAction();

        if (null === $action) {
            return [];
        }

        if ($this->throwable) {
            return [];
        }

        try {
            $resultArray = $this->processor->callAction(
                $this->action,
                $this,
                $result, $input, $context
            );
        }
        catch ( \Throwable $e ) {
            $this->throwable = $e;

            end($this->middlewareList);
            end($this->actionList);

            $resultArray = $this->doFallbacks($input, $context);
        }

        return $resultArray;
    }


    /**
     * @return array{ 0?: mixed }
     */
    protected function doFallbacks($result = null, $input = null, $context = null) : array
    {
        do {
            $outputArray = $this->doCurrentFallback($result, $input, $context);

            if ($this->fallbackIdLast > key($this->fallbackList)) {
                if (count($outputArray)) {
                    [ $result ] = $outputArray;
                }
            }

            next($this->fallbackList);
        } while ( null !== key($this->fallbackList) );

        return $outputArray;
    }

    /**
     * @return array{ 0?: mixed }
     */
    protected function doNextFallback($result = null, $input = null, $context = null) : array
    {
        next($this->fallbackList);

        $resultArray = $this->doCurrentFallback($result, $input, $context);

        return $resultArray;
    }

    /**
     * @return array{ 0?: mixed }
     */
    protected function doCurrentFallback($result = null, $input = null, $context = null) : array
    {
        $fallback = $this->getCurrentFallback();

        if (null === $fallback) {
            return [];
        }

        if (! $this->throwable) {
            return [];
        }

        $resultArray = $this->processor->callFallback(
            $fallback,
            $this,
            $this->throwable, $result, $input, $context
        );

        if (count($resultArray)) {
            $this->throwable = null;

        } else {
            $resultArray = $this->doNextFallback($input, $context);
        }

        return $resultArray;
    }
}
