<?php

namespace Gzhegow\Pipeline\Handler\Action;

use Gzhegow\Pipeline\Lib;
use Gzhegow\Pipeline\PipelineInterface;
use Gzhegow\Pipeline\Handler\GenericHandler;
use Gzhegow\Pipeline\Exception\LogicException;


class GenericAction extends GenericHandler
{
    /**
     * @var PipelineInterface
     */
    public $pipeline;


    /**
     * @return static
     */
    public static function from($from) : object
    {
        if (null === ($instance = static::tryFrom($from))) {
            throw new LogicException([
                'Unknown `from`: ' . Lib::php_dump($from),
            ]);
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from) : ?object
    {
        $instance = null
            ?? static::fromStatic($from)
            ?? static::fromPipeline($from)
            ?? static::fromClosure($from)
            ?? static::fromMethod($from)
            ?? static::fromInvokable($from)
            ?? static::fromFunction($from);

        return $instance;
    }


    /**
     * @return static|null
     */
    protected static function fromPipeline($pipeline) : ?object
    {
        if (! is_a($pipeline, PipelineInterface::class)) {
            return Lib::php_trigger_error([ 'The `from` should be instance of: ' . PipelineInterface::class, $pipeline ]);
        }

        $instance = new static();
        $instance->pipeline = $pipeline;

        return $instance;
    }
}
