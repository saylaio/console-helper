<?php

namespace Sayla\Helper\Console\Steps;

class CallableStep implements Step
{
    use StepTrait;
    /** @var callable */
    protected $callback;

    /**
     * CommandStep constructor.
     * @param string $name
     * @param callable $callback
     * @param string $desc
     * @param callable $recoveryStep
     */
    public function __construct(string $name, callable $callback, string $desc = null, callable $recoveryStep = null)
    {
        $this->setContext($name, $desc, $recoveryStep);
        $this->callback = $callback;
    }


    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }
}