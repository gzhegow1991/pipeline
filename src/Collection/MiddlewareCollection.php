<?php

namespace Gzhegow\Pipeline\Collection;

use Gzhegow\Pipeline\Handler\Middleware\GenericMiddleware;


class MiddlewareCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericMiddleware[]
     */
    public $middlewareList = [];

    /**
     * @var array<string, int>
     */
    public $middlewareMapKeyToId;


    public function getMiddleware(int $id) : GenericMiddleware
    {
        return $this->middlewareList[ $id ];
    }


    public function registerMiddleware(GenericMiddleware $middleware) : int
    {
        $key = $middleware->getKey();

        $id = $this->middlewareMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->id;

            $this->middlewareList[ $id ] = $middleware;

            $this->middlewareMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
