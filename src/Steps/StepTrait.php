<?php

namespace Sayla\Helper\Console\Steps;
/**
 * Trait CommandStepTrait
 * @method mixed __invoke
 */
trait StepTrait
{
    /** @var string */
    protected $description;
    /** @var string */
    protected $name;
    /** @var callable */
    protected $recoveryStep;
    private $callbackParams = [];

    public function addParameter(string $name, $value)
    {
        $this->callbackParams[$name] = $value;
        return $this;
    }

    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return \Closure::fromCallable([$this, '__invoke']);
    }

    /**
     * @return array
     */
    public function getCallbackParams(): array
    {
        return $this->callbackParams;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? $this->name;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return callable
     */
    public function getRecoveryStep(): callable
    {
        return $this->recoveryStep;
    }

    /**
     * @param callable $recoveryStep
     * @return \Sayla\Helper\Console\Steps\StepTrait
     */
    public function setRecoveryStep(callable $recoveryStep)
    {
        $this->recoveryStep = $recoveryStep;
        return $this;
    }

    public function hasRecoveryStep(): bool
    {
        return isset($this->recoveryStep);
    }

    private function setContext(string $name, string $desc = null, callable $recoveryStep = null)
    {
        $this->name = $name;
        $this->description = $desc;
        $this->recoveryStep = $recoveryStep;
    }
}