<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\Engines\RemoteConnectionInterface;
use Driver\Engines\ConnectionTrait;
use Driver\System\Configuration;

class Connection implements RemoteConnectionInterface
{
    use ConnectionTrait;

    private Configuration $configuration;
    private Sandbox $sandbox;
    private Ssl $ssl;

    public function __construct(Configuration $configuration, Sandbox $sandbox, Ssl $ssl)
    {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->ssl = $ssl;
    }

    public function useSsl(): bool
    {
        return true;
    }

    public function test(callable $onFailure): void
    {
        try {
            $this->getConnection();
        } catch (\Exception $ex) {
            $onFailure($this);
        }
    }

    public function isAvailable(): bool
    {
        try {
            $this->getConnection();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function authorizeIp(): void
    {
        $this->sandbox->authorizeIp();
    }

    public function getCharset(): string
    {
        if ($charset = $this->configuration->getNode('connections/mysql/charset')) {
            return $charset;
        } else {
            return 'utf8';
        }
    }

    public function getDSN(): string
    {
        return "mysql:host={$this->getHost()};dbname={$this->getDatabase()};"
            . "port={$this->getPort()};charset={$this->getCharset()}";
    }

    public function getHost(): string
    {
        return $this->sandbox->getEndpointAddress();
    }

    public function getPort(): string
    {
        return $this->sandbox->getEndpointPort();
    }

    public function getDatabase(): string
    {
        return $this->sandbox->getDBName();
    }

    public function getUser(): string
    {
        return $this->sandbox->getUsername();
    }

    public function getPassword(): string
    {
        return $this->sandbox->getPassword();
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getPreserve(): array
    {
        return [];
    }
}
