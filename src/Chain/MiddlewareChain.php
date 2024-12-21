<?php

namespace Gzhegow\Pipeline\Chain;

use Gzhegow\Pipeline\Pipe\PipelinePipe;
use Gzhegow\Pipeline\Exception\LogicException;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Handler\Middleware\GenericHandlerMiddleware;


class MiddlewareChain extends AbstractPipelineChain
{
    /**
     * @var PipelinePipe<GenericHandlerMiddleware>
     */
    protected $pipe;


    public function __construct(PipelineFactoryInterface $factory, PipelinePipe $pipe)
    {
        parent::__construct($factory);

        if (null === $pipe->handlerMiddleware) {
            throw new LogicException(
                [
                    'The `pipe` should be wrapper over: ' . GenericHandlerMiddleware::class,
                    $pipe,
                ]
            );
        }

        $this->pipe = $pipe;
    }


    /**
     * @return PipelinePipe<GenericHandlerMiddleware>
     */
    public function getPipe() : PipelinePipe
    {
        return $this->pipe;
    }
}
