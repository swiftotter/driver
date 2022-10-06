<?php

declare(strict_types=1);

namespace Driver\System;

use Driver\Engines\RemoteConnectionInterface;
use Driver\Engines\ConnectionTrait;
use Symfony\Component\Console\Output\ConsoleOutput;

class DebugExternalConnection implements RemoteConnectionInterface
{
    use ConnectionTrait;

    private Configuration $configuration;

    private ConsoleOutput $output;

    public function __construct(Configuration $configuration, ConsoleOutput $output)
    {
        $this->configuration = $configuration;
        $this->output = $output;
    }

    public function test(callable $onFailure): void
    {
    }

    public function authorizeIp(): void
    {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getDSN(): string
    {
        return "mysql:host={$this->getHost()};dbname={$this->getDatabase()};"
            . "port={$this->getPort()};charset={$this->getCharset()}";
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

    public function useSsl(): bool
    {
        return false;
    }

    public function getDatabase(): string
    {
        return $this->getValue('database', true);
    }

    public function getUser(): string
    {
        return $this->getValue('username', true);
    }

    public function getPassword(): string
    {
        return $this->getValue('password', true);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    private function getValue(string $key, bool $required)
    {
        $value = $this->configuration->getNode("connections/mysql_debug/{$key}");
        if (is_array($value) && $required) {
            $this->output->writeln("<error>{$key} is not set. Please set it in a configuration file.</error>");
            throw new \Exception("{$key} is not set. Please set it in a configuration file.");
        }

        return $value;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getPreserve(): array
    {
        return $this->getValue('preserve', false) ?: [];
    }
}
