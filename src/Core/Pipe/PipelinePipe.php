<?php

namespace Gzhegow\Pipeline\Core\Pipe;

use Gzhegow\Lib\Modules\Php\Result\Ret;
use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Pipeline\Core\Chain\PipelineChain;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain;
use Gzhegow\Pipeline\Core\Handler\GenericHandler;
use Gzhegow\Pipeline\Core\Chain\PipelineChainInterface;
use Gzhegow\Pipeline\Core\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Core\Handler\Middleware\GenericHandlerMiddleware;


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
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            //
            ?? static::fromMiddleware($from, $retCur)
            ?? static::fromPipeline($from, $retCur)
            //
            ?? static::fromHandlerAction($from, $retCur)
            ?? static::fromHandlerFallback($from, $retCur)
            ?? static::fromHandlerMiddleware($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        return Result::ok($ret, $instance);
    }


    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromStatic($from, $ret = null)
    {
        if ($from instanceof static) {
            return Result::ok($ret, $from);
        }

        return Result::err(
            $ret,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }


    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromMiddleware($from, $ret = null)
    {
        if (! ($from instanceof MiddlewareChain)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . MiddlewareChain::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->middleware = $from;

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromPipeline($from, $ret = null)
    {
        if (! ($from instanceof PipelineChain)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . PipelineChain::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->pipeline = $from;

        return Result::ok($ret, $instance);
    }


    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromHandlerAction($from, $ret = null)
    {
        if (! ($from instanceof GenericHandlerAction)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . GenericHandlerAction::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerAction = $from;

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromHandlerFallback($from, $ret = null)
    {
        if (! ($from instanceof GenericHandlerFallback)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . GenericHandlerFallback::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerFallback = $from;

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromHandlerMiddleware($from, $ret = null)
    {
        if (! ($from instanceof GenericHandlerMiddleware)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . GenericHandlerMiddleware::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->handlerMiddleware = $from;

        return Result::ok($ret, $instance);
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
