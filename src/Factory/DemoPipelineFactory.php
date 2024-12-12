<?php

namespace Gzhegow\Pipeline\Factory;

use Gzhegow\Pipeline\PipelineFacade;
use Gzhegow\Pipeline\PipelineFactory;
use Gzhegow\Pipeline\PipelineProcessor;
use Gzhegow\Pipeline\PipelineProcessManager;
use Gzhegow\Pipeline\PipelineFacadeInterface;
use Gzhegow\Pipeline\PipelineProcessorInterface;
use Gzhegow\Pipeline\PipelineProcessManagerInterface;


class DemoPipelineFactory extends PipelineFactory
{
    public function makeFacade(
        PipelineProcessManagerInterface $processManager = null,
        //
        PipelineProcessorInterface $processor = null
    ) : PipelineFacadeInterface
    {
        $processor = $processor ?? $this->makeProcessor();

        $processManager = null
            ?? $processManager
            ?? $this->makeProcessManager(
                $processor
            );

        $facade = new PipelineFacade(
            $this,
            $processManager
        );

        return $facade;
    }


    public function makeProcessManager(
        PipelineProcessorInterface $processor = null
    ) : PipelineProcessManagerInterface
    {
        $processor = $processor ?? $this->makeProcessor();

        $processManager = new PipelineProcessManager(
            $this,
            $processor
        );

        return $processManager;
    }

    public function makeProcessor() : PipelineProcessorInterface
    {
        $processor = new PipelineProcessor($this);

        return $processor;
    }
}
