<?php

namespace Gzhegow\Pipeline\Pipe;

use Gzhegow\Pipeline\Chain\PipelineChain;
use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Pipeline\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Handler\GenericHandler;
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
    public static function from($from, $ctx = null)
    {
        Result::parse($cur);

        $instance = null
            ?? static::fromStatic($from, $cur)
            //
            ?? static::fromMiddleware($from, $cur)
            ?? static::fromPipeline($from, $cur)
            //
            ?? static::fromHandlerAction($from, $cur)
            ?? static::fromHandlerFallback($from, $cur)
            ?? static::fromHandlerMiddleware($from, $cur);

        if ($cur->isErr()) {
            return Result::err($ctx, $cur);
        }

        return Result::ok($ctx, $instance);
    }


    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, $ctx = null)
    {
        if ($from instanceof static) {
            return Result::ok($ctx, $from);
        }

        return Result::err(
            $ctx,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }


    /**
     * @return static|bool|null
     */
    public static function fromMiddleware($from, $ctx = null)
    {
        if (! ($from instanceof MiddlewareChain)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be instance of: ' . MiddlewareChain::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->middleware = $from;

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromPipeline($from, $ctx = null)
    {
        if (! ($from instanceof PipelineChain)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be instance of: ' . PipelineChain::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->pipeline = $from;

        return Result::ok($ctx, $instance);
    }


    /**
     * @return static|bool|null
     */
    public static function fromHandlerAction($from, $ctx = null)
    {
        if (! ($from instanceof GenericHandlerAction)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be instance of: ' . GenericHandlerAction::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerAction = $from;

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromHandlerFallback($from, $ctx = null)
    {
        if (! ($from instanceof GenericHandlerFallback)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be instance of: ' . GenericHandlerFallback::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerFallback = $from;

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromHandlerMiddleware($from, $ctx = null)
    {
        if (! ($from instanceof GenericHandlerMiddleware)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be instance of: ' . GenericHandlerMiddleware::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerMiddleware = $from;

        return Result::ok($ctx, $instance);
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
