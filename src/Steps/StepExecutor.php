<?php

namespace Sayla\Helper\Console\Steps;

use Illuminate\Contracts\Container\Container;
use Sayla\Helper\Console\CommandOutputWriter;
use Sayla\Helper\Console\OutputWriter;

class StepExecutor
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;
    /**
     * @var \Sayla\Helper\Console\OutputWriter
     */
    private $writer;

    /**
     * StepExecutor constructor.
     */
    public function __construct(Container $container, OutputWriter $writer)
    {
        $this->container = $container;
        $this->writer = $writer;
    }

    /**
     * @param Step $step
     * @param array $eventParams
     */
    protected function callPostEvent(Step $step, $result, array $eventParams): void
    {
        if ($step instanceof PostRunEvent) {
            $this->container->call(\Closure::fromCallable([$step, 'postRun']), compact('result') + $eventParams);
        }
    }

    /**
     * @param Step $step
     * @param array $eventParams
     */
    protected function callPreEvent(Step $step, array $eventParams): void
    {
        if ($step instanceof PreRunEvent) {
            $this->container->call(\Closure::fromCallable([$step, 'preRun']), $eventParams);
        }
    }

    /**
     * @param Step $step
     * @param $result
     */
    public function callStepEvents(Step $step, $result)
    {
        $eventParams = [OutputWriter::class => $this->writer];
        $this->callPreEvent($step, $eventParams);
        $this->callPostEvent($step, $result, $eventParams);
    }

    /**
     * @param \Sayla\Helper\Console\Steps\Step $step
     * @return bool|mixed
     * @throws \Throwable
     */
    public function executeStep(Step $step)
    {
        $eventParams = [OutputWriter::class => $this->writer];
        $this->callPreEvent($step, $eventParams);
        $result = $this->getStepResult($step);
        $this->callPostEvent($step, $result, $eventParams);
        return $result;
    }

    /**
     * @param \Sayla\Helper\Console\Steps\Step $step
     * @return bool|mixed
     * @throws \Throwable
     */
    protected function getStepResult(Step $step)
    {
        $params = $step->getCallbackParams();
        $params[OutputWriter::class] = $this->writer;
        if ($this->writer instanceof CommandOutputWriter) {
            $params[CommandOutputWriter::class] = $this->writer;
        }
        $result = null;
        try {
            $result = $this->container->call($step->getCallback(), $params);

        } catch (\Throwable $exception) {
            if ($step->hasRecoveryStep()) {
                $this->container->call($step->getRecoveryStep(), $params);
            }
            throw $exception;
        }
        return $result ?? true;
    }
}