<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 4/1/22
 * @website https://swiftotter.com
 **/

namespace Driver\System;

class DebugMode
{
    private $debugMode;

    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    public function get(): bool
    {
        return $this->debugMode;
    }
}
