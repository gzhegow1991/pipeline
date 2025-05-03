<?php

namespace Gzhegow\Pipeline\Core\ProcessManager;

use Gzhegow\Pipeline\Core\Process\PipelineProcessInterface;


interface PipelineProcessManagerInterface
{
    public function run($pipeline, $input = null, $context = null);

    public function next(PipelineProcessInterface $process, $input = null, $context = null);
}
