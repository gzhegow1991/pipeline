<?php

namespace Gzhegow\Pipeline\Pipe;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


/**
 * @template-covariant T of PipelineChainInterface|GenericHandler
 */
class PipelinePipe
{
    /**
     * @var GenericHandlerMiddleware
     */
    protected $handlerMiddleware;

    /**
     * @var GenericHandlerAction
     */
    protected $handlerAction;

    /**
     * @var GenericHandlerFallback
     */
    protected $handlerFallback;


    /**
     * @var MiddlewareChain
     */
    protected $middleware;

    /**
     * @var PipelineChain
     */
    protected $pipeline;


    private function __construct()
    {
    }


    /**
     * @return static|bool|null
     */
    public static function from($from, array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromStatic($from, $refs)
            //
            ?? static::fromMiddleware($from, $refs)
            ?? static::fromPipeline($from, $refs)
            //
            ?? static::fromHandlerAction($from, $refs)
            ?? static::fromHandlerFallback($from, $refs)
            ?? static::fromHandlerMiddleware($from, $refs);

        if (! $withErrors) {
            if (null === $instance) {
                throw $refs[ 0 ];
            }
        }

        return $instance;
    }


    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, array $refs = [])
    {
        if ($from instanceof static) {
            return Lib::refsResult($refs, $from);
        }

        return Lib::refsError(
            $refs,
            new LogicException(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            )
        );
    }


    /**
     * @return static|bool|null
     */
    public static function fromMiddleware($from, array $refs = [])
    {
        if (! ($from instanceof MiddlewareChain)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of: ' . MiddlewareChain::class, $from ]
                )
            );
        }

        $instance = new static();
        $instance->middleware = $from;

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromPipeline($from, array $refs = [])
    {
        if (! ($from instanceof PipelineChain)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of: ' . PipelineChain::class, $from ]
                )
            );
        }

        $instance = new static();
        $instance->pipeline = $from;

        return Lib::refsResult($refs, $instance);
    }


    /**
     * @return static|bool|null
     */
    public static function fromHandlerAction($from, array $refs = [])
    {
        if (! ($from instanceof GenericHandlerAction)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of: ' . GenericHandlerAction::class, $from ]
                )
            );
        }

        $instance = new static();
        $instance->handlerAction = $from;

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromHandlerFallback($from, array $refs = [])
    {
        if (! ($from instanceof GenericHandlerFallback)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of: ' . GenericHandlerFallback::class, $from ]
                )
            );
        }

        $instance = new static();
        $instance->handlerFallback = $from;

        return Lib::refsResult($refs, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromHandlerMiddleware($from, array $refs = [])
    {
        if (! ($from instanceof GenericHandlerMiddleware)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be instance of: ' . GenericHandlerMiddleware::class, $from ]
                )
            );
        }

        $instance = new static();
        $instance->handlerMiddleware = $from;

        return Lib::refsResult($refs, $instance);
    }


    public function hasMiddleware() : ?MiddlewareChain
    {
        return $this->middleware;
    }

    public function getMiddleware() : MiddlewareChain
    {
        return $this->middleware;
    }


    public function hasPipeline() : ?PipelineChain
    {
        return $this->pipeline;
    }

    public function getPipeline() : PipelineChain
    {
        return $this->pipeline;
    }


    public function hasHandlerMiddleware() : ?GenericHandlerMiddleware
    {
        return $this->handlerMiddleware;
    }

    public function getHandlerMiddleware() : GenericHandlerMiddleware
    {
        return $this->handlerMiddleware;
    }


    public function hasHandlerAction() : ?GenericHandlerAction
    {
        return $this->handlerAction;
    }

    public function getHandlerAction() : GenericHandlerAction
    {
        return $this->handlerAction;
    }


    public function hasHandlerFallback() : ?GenericHandlerFallback
    {
        return $this->handlerFallback;
    }

    public function getHandlerFallback() : GenericHandlerFallback
    {
        return $this->handlerFallback;
    }
}
