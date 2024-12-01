<?php

namespace Gzhegow\Pipeline\Pipe;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Chain\ChainInterface;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


/**
 * @template-covariant T of ChainInterface|GenericHandler
 */
class Pipe
{
    /**
     * @var PipelineChain
     */
    public $pipeline;

    /**
     * @var MiddlewareChain
     */
    public $middleware;

    /**
     * @var GenericHandlerMiddleware
     */
    public $handlerMiddleware;

    /**
     * @var GenericHandlerAction
     */
    public $handlerAction;

    /**
     * @var GenericHandlerFallback
     */
    public $handlerFallback;


    public static function from($from) : self
    {
        $instance = static::tryFrom($from);

        return $instance;
    }

    public static function tryFrom($from) : ?self
    {
        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromMiddleware($from)
            ?? static::tryFromHandlerAction($from)
            ?? static::tryFromHandlerFallback($from)
            ?? static::tryFromHandlerMiddleware($from)
            ?? static::tryFromPipeline($from)
        ;

        return $instance;
    }


    public static function tryFromInstance($from) : ?self
    {
        if (! ($from instanceof static)) {
            return null;
        }

        return $from;
    }

    public static function tryFromMiddleware($from) : ?self
    {
        if (! ($from instanceof MiddlewareChain)) {
            return null;
        }

        $instance = new static();
        $instance->middleware = $from;

        return $instance;
    }

    public static function tryFromHandlerAction($from) : ?self
    {
        if (! ($from instanceof GenericHandlerAction)) {
            return null;
        }

        $instance = new static();
        $instance->handlerAction = $from;

        return $instance;
    }

    public static function tryFromHandlerFallback($from) : ?self
    {
        if (! ($from instanceof GenericHandlerFallback)) {
            return null;
        }

        $instance = new static();
        $instance->handlerFallback = $from;

        return $instance;
    }

    public static function tryFromHandlerMiddleware($from) : ?self
    {
        if (! ($from instanceof GenericHandlerMiddleware)) {
            return null;
        }

        $instance = new static();
        $instance->handlerMiddleware = $from;

        return $instance;
    }

    public static function tryFromPipeline($from) : ?self
    {
        if (! ($from instanceof PipelineChain)) {
            return null;
        }

        $instance = new static();
        $instance->pipeline = $from;

        return $instance;
    }
}
