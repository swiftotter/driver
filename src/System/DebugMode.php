<?php

declare(strict_types=1);

namespace Driver\System;

class DebugMode
{
    private bool $debugMode;

    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    public function get(): bool
    {
        return $this->debugMode;
    }
}
