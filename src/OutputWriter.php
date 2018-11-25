<?php

namespace Sayla\Helper\Console;

interface OutputWriter
{
    public function alert($string);

    public function error($string);

    public function info($string);

    public function line($string);

    public function warn($string);
}