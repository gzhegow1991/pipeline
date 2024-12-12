<?php

namespace Gzhegow\Pipeline\Chain;

use Gzhegow\Pipeline\Pipe\Pipe;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\ProcessManager\ProcessManagerInterface;
use Gzhegow\Pipeline\Handler\Action\GenericHandlerAction;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


abstract class AbstractChain implements ChainInterface
{
    /**
     * @var PipelineFactoryInterface
     */
    protected $factory;
    /**
     * @var ProcessManagerInterface
     */
    protected $processManager;

    /**
     * @var ChainInterface
     */
    protected $parent;

    /**
     * @var Pipe<ChainInterface|GenericHandler>[]
     */
    protected $pipes = [];
    /**
     * @var \Throwable[]
     */
    protected $throwables = [];


    public function __construct(PipelineFactoryInterface $factory)
    {
        $this->factory = $factory;
    }


    /**
     * @param ProcessManagerInterface $processManager
     *
     * @return static
     */
    public function setProcessManager(ProcessManagerInterface $processManager) // : static
    {
        $this->processManager = $processManager;

        return $this;
    }


    /**
     * @return Pipe<ChainInterface|GenericHandler>[]
     */
    public function getPipes() : array
    {
        return $this->pipes;
    }

    /**
     * @return static
     */
    public function pipeline(PipelineChain $from) // : static
    {
        $pipe = Pipe::from($from);

        $this->pipes[] = $pipe;

        return $this;
    }

    public function startPipeline() : PipelineChain
    {
        $pipeline = $this->factory->newPipeline();

        $this->pipeline($pipeline);

        $pipeline->parent = $this;

        return $pipeline;
    }

    public function endPipeline() : ChainInterface
    {
        if (null === ($parent = $this->parent)) {
            throw new RuntimeException('No parent pipeline');
        }

        $this->parent = null;

        return $parent;
    }


    /**
     * @return static
     */
    public function middleware(MiddlewareChain $from) // : static
    {
        $pipe = Pipe::from($from);

        $this->pipes[] = $pipe;

        return $this;
    }

    public function startMiddleware($from) : MiddlewareChain
    {
        $middleware = $this->factory->newMiddleware($from);

        $this->middleware($middleware);

        $middleware->parent = $this;

        return $middleware;
    }

    public function endMiddleware() : ChainInterface
    {
        if (null === ($parent = $this->parent)) {
            throw new RuntimeException('No parent middleware');
        }

        $this->parent = null;

        return $parent;
    }


    /**
     * @return static
     */
    public function action($from) // : static
    {
        $generic = GenericHandlerAction::from($from);

        $pipe = Pipe::from($generic);

        $this->pipes[] = $pipe;

        return $this;
    }

    /**
     * @return static
     */
    public function fallback($from) // : static
    {
        $generic = GenericHandlerFallback::from($from);

        $pipe = Pipe::from($generic);

        $this->pipes[] = $pipe;

        return $this;
    }


    /**
     * @return \Throwable[]
     */
    public function getThrowables() : array
    {
        return $this->throwables;
    }

    public function latestThrowable() : ?\Throwable
    {
        $throwable = end($this->throwables);

        if (null === key($this->throwables)) {
            return null;
        }

        return $throwable;
    }

    public function popThrowable() : ?\Throwable
    {
        if (null === ($throwable = $this->latestThrowable())) {
            return null;
        }

        array_pop($this->throwables);

        return $throwable;
    }

    public function throwable(\Throwable $throwable) // : static
    {
        $this->throwables[] = $throwable;

        return $this;
    }


    public function run($input = null, $context = null) // : mixed
    {
        if (! $this->processManager) {
            throw new RuntimeException(
                'You have to call ->setProcessManager() to use method ->run() directly from chain'
            );
        }

        $result = $this->processManager->run($this, $input, $context);

        return $result;
    }
}
