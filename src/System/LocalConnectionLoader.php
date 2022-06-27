<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 2/8/20
 * @website https://swiftotter.com
 **/

namespace Driver\System;

use DI\Container;
use Driver\Engines\ConnectionInterface;
use Driver\Engines\LocalConnectionInterface;
use Driver\Engines\PersistentPDO;

class LocalConnectionLoader implements LocalConnectionInterface
{
    /** @var Configuration */
    private $configuration;

    /** @var DefaultConnection */
    private $defaultConnection;

    /** @var ConnectionInterface */
    private $connection;

    /** @var Container */
    private $container;

    public function __construct(
        Configuration $configuration,
        Container $container,
        DefaultConnection $defaultConnection
    ) {
        $this->configuration = $configuration;
        $this->defaultConnection = $defaultConnection;
        $this->container = $container;
    }

    public function getConnection(): PersistentPDO
    {
        return $this->get()->getConnection();
    }

    private function get()
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

    private function getDefault()
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

    public function getPreserve(): array
    {
        return $this->get()->getPreserve();
    }
}
