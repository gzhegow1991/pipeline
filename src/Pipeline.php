<?php

namespace Gzhegow\Pipeline;

use Gzhegow\Pipeline\Exception\Runtime\PipelineException;
use Gzhegow\Pipeline\Core\Chain\PipelineChain as PipelineChain;
use Gzhegow\Pipeline\Core\Chain\MiddlewareChain as MiddlewareChain;


class Pipeline
{
    public static function pipeline() : PipelineChain
    {
        $pipeline = static::$facade->pipeline();

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
    public static function run($pipeline, $input = null, $context = null)
    {
        $result = static::$facade->run($pipeline, $input, $context);

        return $result;
    }


    public static function setFacade(?PipelineInterface $facade) : ?PipelineInterface
    {
        $last = static::$facade;

        static::$facade = $facade;

        return $last;
    }

    /**
     * @var PipelineInterface
     */
    protected static $facade;
}
