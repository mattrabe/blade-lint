<?php

namespace MattRabe\BladeLint;

abstract class Rule
{
    abstract public function __construct(string $file, array $options);

    abstract public function test();

    abstract public function fix();
}
