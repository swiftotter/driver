<?php

declare(strict_types=1);

namespace Driver\System;

use DI\Container;
use Driver\Engines\ConnectionInterface;
use Driver\Engines\LocalConnectionInterface;
use Driver\Engines\ReconnectingPDO;

class LocalConnectionLoader implements LocalConnectionInterface
{
    private Configuration $configuration;
    private Container $container;
    private DefaultConnection $defaultConnection;
    private ?ConnectionInterface $connection = null;

    public function __construct(
        Configuration $configuration,
        Container $container,
        DefaultConnection $defaultConnection
    ) {
        $this->configuration = $configuration;
        $this->defaultConnection = $defaultConnection;
        $this->container = $container;
    }

    public function getConnection(): ReconnectingPDO
    {
        return $this->get()->getConnection();
    }

    private function get(): ConnectionInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connections = $this->configuration->getNode('connections/source') ?? [];

        foreach ($connections as $connection) {
            if (!isset($connection['lookup']) || !class_exists($connection['lookup'])) {
                continue;
            }

            /** @var ConnectionInterface $connection */
            $connection = $this->container->make($connection['lookup'], [
                'settings' => $connection
            ]);

            if ($connection->isAvailable()) {
                $this->connection = $connection;
                return $this->connection;
            }
        }

        if (!$this->connection) {
            $this->connection = $this->getDefault();
        }

        return $this->connection;
    }

    private function getDefault(): DefaultConnection
    {
        return $this->defaultConnection;
    }

    public function isAvailable(): bool
    {
        return $this->get()->isAvailable();
    }

    public function getDSN(): string
    {
        return $this->get()->getDSN();
    }

    public function getCharset(): string
    {
        return $this->get()->getCharset();
    }

    public function getHost(): string
    {
        return $this->get()->getHost() ?? 'localhost';
    }

    public function getPort(): string
    {
        return $this->get()->getPort();
    }

    public function getDatabase(): string
    {
        return $this->get()->getDatabase();
    }

    public function getUser(): string
    {
        return $this->get()->getUser();
    }

    public function getPassword(): string
    {
        return $this->get()->getPassword();
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getPreserve(): array
    {
        return $this->get()->getPreserve();
    }
}
