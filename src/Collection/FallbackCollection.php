<?php

namespace Gzhegow\Pipeline\Collection;

use Gzhegow\Pipeline\Handler\Fallback\GenericFallback;


class FallbackCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericFallback[]
     */
    public $fallbackList = [];

    /**
     * @var array<string, int>
     */
    public $fallbackMapKeyToId;


    public function getFallback(int $id) : GenericFallback
    {
        return $this->fallbackList[ $id ];
    }


    public function registerFallback(GenericFallback $fallback) : int
    {
        $key = $fallback->getKey();

        $id = $this->fallbackMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->id;

            $this->fallbackList[ $id ] = $fallback;

            $this->fallbackMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
