<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Exception\RuntimeException;
use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


class Pipeline
{
    /**
     * @var PipelineFactory
     */
    protected $factory;

    /**
     * @var PipelineProcessManager
     */
    protected $processManager;


    public function __construct(
        PipelineFactory $factory,
        PipelineProcessManager $processManager
    )
    {
        $this->factory = $factory;
        $this->processManager = $processManager;
    }


    protected function doNew() : PipelineChain
    {
        $pipeline = $this->factory->newPipeline();

        return $pipeline;
    }

    protected function doMiddleware($from) : MiddlewareChain
    {
        $middleware = $this->factory->newMiddleware($from);

        return $middleware;
    }

    /**
     * @throws PipelineException
     */
    protected function doRun($pipeline, $input = null, $context = null) // : mixed
    {
        $result = $this->processManager->run(
            $pipeline,
            $input, $context
        );

        return $result;
    }


    public static function new() : PipelineChain
    {
        return static::getInstance()->doNew();
    }

    public static function middleware($from) : MiddlewareChain
    {
        return static::getInstance()->doMiddleware($from);
    }

    /**
     * @throws PipelineException
     */
    public static function run($pipeline, $input = null, $context = null) // : mixed
    {
        $result = static::getInstance()->doRun($pipeline, $input, $context);

        return $result;
    }


    /**
     * @return static
     */
    public static function getInstance(self $facade = null) // : static
    {
        $_facade = null
            ?? $facade
            ?? static::$instances[ static::class ]
            ?? null;

        if (null === $_facade) {
            throw new RuntimeException(
                'You have to call Pipeline::setInstance() to use facade statically'
            );
        }

        return static::$instances[ static::class ] = $_facade;
    }

    public static function setInstance(?self $facade) : void
    {
        static::$instances[ static::class ] = $facade;
    }

    /**
     * @var static[]
     */
    protected static $instances = [];
}
