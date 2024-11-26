<?php

namespace Gzhegow\Pipeline;


class PipelineFactory implements PipelineFactoryInterface
{
    public function newPipeline(PipelineProcessorInterface $processor = null) : PipelineInterface
    {
        $processor = $processor ?? $this->newPipelineProcessor();

        $pipeline = new Pipeline($processor);

        return $pipeline;
    }

    public function newPipelineProcessor() : PipelineProcessorInterface
    {
        $pipelineProcessor = new PipelineProcessor($this);

        return $pipelineProcessor;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        [ $list ] = Lib::array_kwargs($parameters);

        $handler = new $class(...$list);

        return $handler;
    }
}
