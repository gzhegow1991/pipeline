<?php

namespace Gzhegow\Pipeline\Pipe;

use Gzhegow\Pipeline\Pipeline;
use Gzhegow\Pipeline\Handler\GenericHandler;


class Pipe
{
    const TYPE_PIPELINE   = 1 << 0;
    const TYPE_MIDDLEWARE = 1 << 1;
    const TYPE_ACTION     = 1 << 2;
    const TYPE_FALLBACK   = 1 << 3;
    // const TYPE_PIPELINE   = 'PIPELINE';
    // const TYPE_MIDDLEWARE = 'MIDDLEWARE';
    // const TYPE_ACTION     = 'ACTION';
    // const TYPE_FALLBACK   = 'FALLBACK';

    const LIST_TYPE = [
        self::TYPE_PIPELINE   => 'pipelineList',
        self::TYPE_MIDDLEWARE => 'middlewareList',
        self::TYPE_ACTION     => 'actionList',
        self::TYPE_FALLBACK   => 'fallbackList',
    ];


    /**
     * @var static::TYPE_PIPELINE|static::TYPE_MIDDLEWARE|static::TYPE_ACTION|static::TYPE_FALLBACK
     */
    protected $type;
    /**
     * @var Pipeline
     */
    protected $pipeline;
    /**
     * @var GenericHandler
     */
    protected $handler;


    public function __construct(int $type, Pipeline $pipeline = null, GenericHandler $handler = null)
    {
        if (! isset(static::LIST_TYPE[ $type ])) {
            throw new \LogicException(
                'Unknown `type`: ' . $type
            );
        }

        $this->type = $type;
        $this->pipeline = $pipeline;
        $this->handler = $handler;
    }


    public function getType() : int
    {
        return $this->type;
    }

    public function getHandler() : ?GenericHandler
    {
        return $this->handler;
    }

    public function getPipeline() : ?Pipeline
    {
        return $this->pipeline;
    }
}
