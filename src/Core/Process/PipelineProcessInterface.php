<?php

namespace Gzhegow\Pipeline\Core\Process;


interface PipelineProcessInterface
{
    public function reset() : void;

    public function run($input = null, $context = null);

    public function next($input = null, $context = null);


    /**
     * @return \Throwable[]
     */
    public function getThrowables() : array;

    public function latestThrowable() : ?\Throwable;

    public function popThrowable() : ?\Throwable;

    /**
     * @return static
     */
    public function addThrowable(\Throwable $throwable);
}
