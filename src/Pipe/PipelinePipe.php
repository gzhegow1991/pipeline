<?php

namespace Gzhegow\Pipeline\Pipe;

use Gzhegow\Lib\Lib;
use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Pipeline\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


/**
 * @template-covariant T of PipelineChainInterface|GenericHandler
 */
class PipelinePipe
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
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    public static function tryFrom($from, \Throwable &$last = null) : ?self
    {
        $last = null;

        Lib::php_errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromMiddleware($from)
            ?? static::tryFromHandlerAction($from)
            ?? static::tryFromHandlerFallback($from)
            ?? static::tryFromHandlerMiddleware($from)
            ?? static::tryFromPipeline($from);

        $errors = Lib::php_errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, null, $last);
            }
        }

        return $instance;
    }


    public static function tryFromInstance($instance) : ?self
    {
        if (! ($instance instanceof static)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    public static function tryFromMiddleware($middleware) : ?self
    {
        if (! ($middleware instanceof MiddlewareChain)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . MiddlewareChain::class, $middleware ]
            );
        }

        $instance = new static();
        $instance->middleware = $middleware;

        return $instance;
    }

    public static function tryFromHandlerAction($handlerAction) : ?self
    {
        if (! ($handlerAction instanceof GenericHandlerAction)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . GenericHandlerAction::class, $handlerAction ]
            );
        }

        $instance = new static();
        $instance->handlerAction = $handlerAction;

        return $instance;
    }

    public static function tryFromHandlerFallback($handlerFallback) : ?self
    {
        if (! ($handlerFallback instanceof GenericHandlerFallback)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . GenericHandlerFallback::class, $handlerFallback ]
            );
        }

        $instance = new static();
        $instance->handlerFallback = $handlerFallback;

        return $instance;
    }

    public static function tryFromHandlerMiddleware($handlerMiddleware) : ?self
    {
        if (! ($handlerMiddleware instanceof GenericHandlerMiddleware)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . GenericHandlerMiddleware::class, $handlerMiddleware ]
            );
        }

        $instance = new static();
        $instance->handlerMiddleware = $handlerMiddleware;

        return $instance;
    }

    public static function tryFromPipeline($pipeline) : ?self
    {
        if (! ($pipeline instanceof PipelineChain)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . PipelineChain::class, $pipeline ]
            );
        }

        $instance = new static();
        $instance->pipeline = $pipeline;

        return $instance;
    }
}
