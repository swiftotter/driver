<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 2/8/20
 * @website https://swiftotter.com
 **/

namespace Driver\System;

use Driver\Engines\ConnectionInterface;
use Driver\Engines\ConnectionTrait;

class DefaultConnection implements ConnectionInterface
{
    use ConnectionTrait;

    /** @var Configuration  */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getDSN(): string
    {
        return "mysql:host={$this->getHost()};dbname={$this->getDatabase()};port={$this->getPort()};charset={$this->getCharset()}";
    }

    public function getCharset(): string
    {
        if ($charset = $this->getValue('charset', false)) {
            return $charset;
        } else {
            return 'utf8';
        }
    }

    public function getHost(): string
    {
        if ($host = $this->getValue('host', false)) {
            return $host;
        } else {
            return 'localhost';
        }
    }

    public function getPort(): string
    {
        if ($port = $this->getValue('port', false)) {
            return $port;
        } else {
            return '3306';
        }
    }

    public function getDatabase(): string
    {
        return $this->getValue('database', true);
    }

    public function getUser(): string
    {
        return $this->getValue('user', true);
    }

    public function getPassword(): string
    {
        return $this->getValue('password', true);
    }

    private function getValue($key, $required)
    {
        $value = $this->configuration->getNode("connections/mysql/{$key}");
        if (is_array($value) && $required) {
            throw new \Exception("{$key} is not set. Please set it in a configuration file.");
        }

        return $value;
    }
}