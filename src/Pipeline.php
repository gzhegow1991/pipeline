<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Exception\Runtime\PipelineException;
use Gzhegow\Pipeline\Chain\MiddlewareChain as MiddlewareChain;


class Pipeline
{
    public static function new() : PipelineChain
    {
        $pipeline = static::$facade->new();

        return $pipeline;
    }

    public static function middleware($from) : MiddlewareChain
    {
        $middleware = static::$facade->middleware($from);

        return $middleware;
    }


    /**
     * @throws PipelineException
     */
    public static function run($pipeline, $input = null, $context = null) // : mixed
    {
        $result = static::$facade->run($pipeline, $input, $context);

        return $result;
    }


    public static function setFacade(PipelineFacadeInterface $facade) : void
    {
        static::$facade = $facade;
    }

    /**
     * @var PipelineFacadeInterface
     */
    protected static $facade;
}
