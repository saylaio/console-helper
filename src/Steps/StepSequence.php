<?php
/**
 * Inspired by sixlive/honeybadger-laravel
 */

namespace Sayla\Helper\Console\Steps;

use Illuminate\Console\Command;
use Sayla\Helper\Console\CommandOutputWriter;
use Sayla\Helper\Console\OutputWriter;
use Sayla\Util\Mixin\Mixin;
use Sayla\Util\Mixin\MixinSet;
use Sayla\Util\Mixin\ProvidesMixins;

class StepSequence
{
    private const OBSERVABLE_METHODS = [
        'preRun' => null,
        'postRun' => null,
        'preStepExecution' => null,
        'postStepExecution' => null
    ];
    /** @var \Sayla\Helper\Console\Steps\StepExecutor */
    protected $executor;
    /**
     * @var array
     */
    protected $results = [];
    /**
     * @var \Sayla\Helper\Console\Steps\Step[]
     */
    protected $steps = [];
    /**
     * @var bool
     */
    protected $throwOnError = true;
    protected $values = [];
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;
    /** @var \Sayla\Helper\Console\Steps\Step */
    private $currentStep;
    /** @var \Sayla\Helper\Console\Steps\ExecutionObserver[] */
    private $observers = [];
    /**
     * @var \Sayla\Helper\Console\OutputWriter
     */
    private $writer;

    /**
     * CommandSteps constructor.
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Sayla\Helper\Console\OutputWriter|\Illuminate\Console\Command $writer
     */
    public function __construct(\Illuminate\Contracts\Container\Container $container, $writer)
    {
        $this->mixinSet = new MixinSet();
        $this->container = $container;
        $this->setWriter(($writer instanceof Command) ? new CommandOutputWriter($writer) : $writer);
    }


    /**
     * @param string $methodName
     * @param array $arguments
     * @return mixed|string
     */
    public function __call(string $methodName, array $arguments)
    {
        $result = $this->mixinSet->call($methodName, $arguments);
        if (str_is('push*Step', $methodName) || $result instanceof Step) {
            $this->putStep($result);
        }
        return $result;
    }

    public function addMixin(Mixin $mixin)
    {
        $this->mixinSet->add($mixin);
        return $this;
    }

    /**
     * @param \Sayla\Helper\Console\Steps\ExecutionObserver $observer
     */
    public function addObserver(\Sayla\Helper\Console\Steps\ExecutionObserver $observer): void
    {
        $this->observers[] = $observer;
        if ($observer instanceof ProvidesMixins) {
            $observer->addMixins($this->mixinSet);
        }
    }

    public function buildObserverFrom($object)
    {
        if ($object instanceof ExecutionObserver) {
            $this->addObserver($object);
        } else {
            if (isset($makeParameters) || is_string($object)) {
                $object = $this->container->make($object, $makeParameters ?? []);
            }
            $methods = self::OBSERVABLE_METHODS;
            foreach (array_keys(self::OBSERVABLE_METHODS) as $methodName) {
                if (method_exists($object, $methodName)) {
                    $methods[$methodName] = \Closure::fromCallable([$object, $methodName]);
                }
            }
            $this->addObserver(new RuntimeObserver(
                $methods['preRun'],
                $methods['postRun'],
                $methods['preStepExecution'],
                $methods['postStepExecution']
            ));
            if ($object instanceof ProvidesMixins) {
                $object->addMixins($this->mixinSet);
            }
        }
        return $this;
    }

    /**
     * @param callable $callable
     * @param array $params
     */
    public function callCallable(callable $callable, array $params)
    {
        $result = $this->container->call($callable, $params);
        if ($result instanceof \Closure) {
            $this->container->call($result, $params);
        }
    }

    /**
     * @param \Sayla\Helper\Console\Steps\Step $step
     * @param \Sayla\Helper\Console\OutputWriter $writer
     * @param $result
     */
    protected function displayStepStatus(Step $step, OutputWriter $writer, $result): void
    {
        $writer->getOutput()->writeLn(vsprintf('%s: %s', [
            $step->getDescription(),
            $result ? '<fg=green>✔</>' : '<fg=red>✘</>',
        ]));
    }

    /**
     * @return self
     */
    public function doNotThrowExceptions(): self
    {
        $this->throwOnError = false;

        return $this;
    }

    public function getCallableMethods()
    {
        return $this->mixinSet->getCallableMethods();
    }

    /**
     * @return \Sayla\Helper\Console\Steps\ExecutionObserver[]
     */
    public function getExecutionObservers(): \Sayla\Helper\Console\Steps\ExecutionObserver
    {
        return $this->observers;
    }

    protected function getExecutor(): StepExecutor
    {
        return $this->executor ?? $this->executor = new StepExecutor($this->container, $this->writer);
    }

    /**
     * Get all step results.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param $stepName
     * @return object
     */
    public function getStep($stepName)
    {
        if (!isset($this->steps[$stepName])) {
            throw new \UnexpectedValueException('Step has not been defined: ' . $stepName);
        }
        return $this->steps[$stepName];
    }

    /**
     * @return string[]
     */
    public function getStepNames(): array
    {
        return array_keys($this->steps);
    }

    /**
     * @return \Sayla\Helper\Console\OutputWriter
     */
    public function getWriter(): \Sayla\Helper\Console\OutputWriter
    {
        return $this->writer;
    }

    /**
     * @param \Sayla\Helper\Console\OutputWriter $writer
     */
    public function setWriter(\Sayla\Helper\Console\OutputWriter $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * @return bool
     */
    public function hasFailedSteps(): bool
    {
        return in_array(false, $this->results);
    }

    /**
     * Add step with result to the stack.
     *
     * @param  string $name
     * @param  callable $step
     * @return \Sayla\Helper\Console\Steps\Step
     */
    public function pushStep(string $name, callable $step, string $desc = null): Step
    {
        $this->putStep(new CallableStep($name, $step, $desc));
        return $this->currentStep;
    }

    /**
     * @param \Sayla\Helper\Console\Steps\Step $sequenceStep
     */
    public function putStep(Step $sequenceStep): void
    {
        $this->steps[$sequenceStep->getName()] = $sequenceStep;
        $this->currentStep = $sequenceStep;
    }

    protected function runStep(Step $step)
    {
        $result = $this->getExecutor()->executeStep($step);
        $this->displayStepStatus($step, $this->writer, $result);
        return $result;
    }

    protected function runStepEvents(Step $step, $result)
    {
        $this->getExecutor()->callStepEvents($step, $result);
        $this->displayStepStatus($step, $this->writer, $result);
        return $result;
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function runSteps(): void
    {
        $executionObservers = $this->observers;
        $observerParams = [OutputWriter::class => $this->writer];

        try {
            foreach ($executionObservers as $observer) {
                $preRunParams = $observerParams + [MixinSet::class => $this->mixinSet];
                $this->callCallable([$observer, 'preRun'], $preRunParams);
            }

            $steps = $this->steps;

            if (empty($this->steps)) {
                return;
            }

            foreach ($steps as $step) {
                try {
                    $observerStepParams = $observerParams;
                    $observerStepParams['step'] = $step;
                    $observerStepParams[Step::class] = $step;

                    foreach ($executionObservers as $observer)
                        $this->callCallable([$observer, 'preStepExecution'], $observerStepParams);

                    if (array_key_exists($step->getName(), $this->values)) {
                        $result = $this->values[$step->getName()];
                        $this->runStepEvents($step, $result);
                    } else {
                        $result = $this->runStep($step);
                    }

                    if ($result === false) {
                        $errMessage = sprintf('%s failed.', $step->getName());
                        throw new StepFailed($errMessage, 0);
                    }

                    $this->results[$step->getName()] = $result;

                    $observerStepParams['result'] = $result;
                    foreach ($executionObservers as $observer) {
                        $this->callCallable([$observer, 'postStepExecution'], $observerStepParams);
                    }

                } catch (\Throwable $exception) {
                    $errMessage = sprintf('%s failed.', $step->getName());
                    throw new StepFailed($errMessage, 0, $exception);
                }
            }

            $observerParams['results'] = $this->results;
            foreach ($executionObservers as $observer)
                $this->callCallable([$observer, 'postRun'], $observerParams);

        } catch (\Throwable $exception) {

            if ($this->throwOnError) {
                throw $exception;
            } else {
                $this->writer->error($exception->getMessage());
            }
        }
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = array_only($values, $this->getStepNames());
        return $this;
    }
}