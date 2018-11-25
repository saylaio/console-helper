<?php

namespace Sayla\Helper\Console\Steps;


final class RuntimeObserver implements ExecutionObserver
{
    /** @var callable */
    private $postRun;
    /** @var callable */
    private $postStepExecution;
    /** @var callable */
    private $preRun;
    /** @var callable */
    private $preStepExecution;

    /**
     * BuiltObserver constructor.
     * @param \Closure|null $preRun
     * @param \Closure|null $postRun
     * @param \Closure|null $preStepExecution
     * @param \Closure|null $postStepExecution
     * @param array $mixins
     */
    public function __construct(?\Closure $preRun, ?\Closure $postRun, ?\Closure $preStepExecution,
                                ?\Closure $postStepExecution)
    {
        $this->preRun = $preRun;
        $this->postRun = $postRun;
        $this->preStepExecution = $preStepExecution;
        $this->postStepExecution = $postStepExecution;
    }

    public function postRun()
    {
        return $this->postRun;
    }

    public function postStepExecution(Step $step, $result)
    {
        return $this->postStepExecution;
    }

    public function preRun()
    {
        return $this->preRun;
    }

    public function preStepExecution(Step $step)
    {
        return $this->preStepExecution;
    }
}