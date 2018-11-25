<?php

namespace Sayla\Helper\Console\Steps;

interface ExecutionObserver
{
    public function postRun();

    public function postStepExecution(Step $step, $result);

    public function preRun();

    public function preStepExecution(Step $step);
}