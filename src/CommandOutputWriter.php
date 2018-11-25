<?php

namespace Sayla\Helper\Console;


use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * @package Saybol\Foundation\Console
 * @method int call($command, array $arguments = [])
 * @method int callSilent($command, array $arguments = [])
 * @method bool confirm($question, $default = false)
 * @method mixed ask($question, $default = null)
 * @method mixed anticipate($question, array $choices, $default = null)
 * @method mixed askWithCompletion($question, array $choices, $default = null)
 * @method mixed secret($question, $fallback = true)
 * @method string choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
 * @method void table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
 * @method void comment($string, $verbosity = null)
 * @method void question($string, $verbosity = null)
 * @method \Illuminate\Console\OutputStyle getOutput()
 */
class CommandOutputWriter implements OutputWriter
{
    use ConfirmableTrait;
    /** @var Command */
    protected $command;

    /**
     * CommandProxy constructor.
     * @param \Illuminate\Console\Command $command
     */
    public function __construct(\Illuminate\Console\Command $command)
    {
        $this->command = $command;
    }

    public function __call(string $method, array $args)
    {
        switch ($method) {
            case 'call':
            case 'callSilent':
            case 'confirm':
            case 'ask':
            case 'anticipate':
            case 'askWithCompletion':
            case 'secret':
            case 'choice':
            case 'table':
            case 'info':
            case 'line':
            case 'comment':
            case 'question':
            case 'error':
            case 'warn':
            case 'alert':
            case 'getOutput':
                return $this->command->{$method}(...$args);
            default:
                throw new \BadMethodCallException($method . ' does not exist');
        }
    }

    public function __debugInfo()
    {
        return [
            'command' => get_class($this->command)
        ];
    }

    public function alert($string, $verbosity = null)
    {
        $this->command->alert($string, $verbosity);
    }

    /**
     * @param string $name
     * @return array|string|null
     */
    protected function argument(string $name)
    {
        return $this->command->argument($name);
    }

    /**
     * Write a string of debug level
     * @param  string $string
     * @param  string $style
     * @return void
     */
    public function debug($string, $style = null)
    {
        $this->command->line($string, $style, 'vvv');
    }

    public function error($string, $verbosity = null)
    {
        $this->command->error($string, $verbosity);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function getLaravel()
    {
        return $this->command->getLaravel();
    }

    public function info($string, $verbosity = null)
    {
        $this->command->info($string, $verbosity);
    }

    public function line($string, $style = null, $verbosity = null)
    {
        $this->command->line($string, $style, $verbosity);
    }

    /**
     * Write a string of very verbose level
     * @param  string $string
     * @param  string $style
     * @return void
     */
    public function moreVerbose($string, $style = null)
    {
        $this->command->line($string, $style, 'vv');
    }

    /**
     * @param string $name
     * @return array|string|null
     */
    protected function option(string $name)
    {
        return $this->command->option($name);
    }

    /**
     * Write a string of quiet level
     * @param  string $string
     * @param  string $style
     * @return void
     */
    public function quietLine($string, $style = null)
    {
        $this->command->line($string, $style, 'quiet');
    }

    /**
     * Write a string of verbose level
     *
     * @param  string $string
     * @param  string $style
     * @return void
     */
    public function verbose($string, $style = null)
    {
        $this->command->line($string, $style, 'v');
    }

    public function warn($string, $verbosity = null)
    {
        $this->command->warn($string, $verbosity);
    }
}
