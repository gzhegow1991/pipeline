<?php

namespace Gzhegow\Pipeline\ProcessManager;

use Gzhegow\Pipeline\Process\PipelineProcessInterface;


interface ProcessManagerInterface
{
    public function run($pipeline, $input = null, $context = null);

    public function next(PipelineProcessInterface $process, $input = null, $context = null);
}
