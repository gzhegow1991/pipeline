<?php

namespace Gzhegow\Pipeline\Process;


interface PipelineProcessInterface
{
    public function getCurrentNestedProcess() : ?PipelineProcessInterface;

    /**
     * @return static
     */
    public function setCurrentNestedProcess(?PipelineProcessInterface $process); // : static


    public function reset() : void;

    public function run($input = null, $context = null);  // : mixed

    public function next($input = null, $context = null); // : mixed


    /**
     * @return \Throwable[]
     */
    public function getThrowables() : array;

    public function latestThrowable() : ?\Throwable;

    public function popThrowable() : ?\Throwable;

    /**
     * @return static
     */
    public function addThrowable(\Throwable $throwable); // : static
}
