<?php

namespace Gzhegow\Pipeline\Chain;

use Gzhegow\Pipeline\Lib;
use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


class MiddlewareChain extends AbstractChain
{
    /**
     * @var Pipe<GenericHandlerMiddleware>
     */
    protected $pipe;


    public function __construct(Pipe $pipe)
    {
        if (null === $pipe->handlerMiddleware) {
            throw new LogicException(
                [
                    'The `pipe` should be wrapper over: ' . GenericHandlerMiddleware::class
                    . ' / Pipe: ' . Lib::php_dump($pipe),
                    $pipe,
                ]
            );
        }

        $this->pipe = $pipe;
    }


    /**
     * @return Pipe<GenericHandlerMiddleware>
     */
    public function getPipe() : Pipe
    {
        return $this->pipe;
    }
}
