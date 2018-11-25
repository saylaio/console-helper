<?php

namespace Sayla\Helper\Console\Steps;


interface Step
{

    public function addParameter(string $name, $value);

    /**
     * @return callable
     */
    public function getCallback(): callable;

    /**
     * @return array
     */
    public function getCallbackParams(): array;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return callable
     */
    public function getRecoveryStep(): callable;

    public function hasRecoveryStep(): bool;

    /**
     * @param callable $recoveryStep
     */
    public function setRecoveryStep(callable $recoveryStep);
}