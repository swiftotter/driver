<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Transformation\Anonymize;

use Driver\Engines\RemoteConnectionInterface;
use Driver\System\Configuration;

use function uniqid;

class Seed
{
    public const FAKE_USER_TABLE = 'fake_users';

    private RemoteConnectionInterface $connection;
    private Configuration $configuration;
    private string $salt;
    private int $count = 0;

    public function __construct(Configuration $configuration, RemoteConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
        $this->salt = uniqid();
    }

    public function initialize(): void
    {
        $this->clean();
        $this->createTable();

        $table = self::FAKE_USER_TABLE;
        foreach ($this->configuration->getNode('anonymize/seed') as $seed) {
            $columns = implode(', ', array_keys($seed));
            $bind = [];
            foreach (array_keys($seed) as $columnName) {
                $bind[] = ':' . $columnName;
            }

            $placeholders = implode(', ', $bind);
            $query = "INSERT INTO ${table} (${columns}) VALUES(${placeholders})";
            $params = array_combine($bind, array_values($seed));

            $this->connection->getConnection()->prepare($query)->execute($params);
            $this->count++;
        }
    }

    public function destroy(): void
    {
        $this->clean();
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    private function clean(): void
    {
        $this->connection->getConnection()->query('DROP TABLE IF EXISTS ' . self::FAKE_USER_TABLE);
        $this->count = 0;
    }

    private function createTable(): void
    {
        $table = self::FAKE_USER_TABLE;

        $this->connection->getConnection()->query(<<<TABLE
CREATE TABLE ${table} (
    id int auto_increment primary key,
    firstname VARCHAR(200),
    lastname VARCHAR(200),
    company VARCHAR(200),
    street VARCHAR(200),
    city VARCHAR(200),
    postcode VARCHAR(200),
    phone VARCHAR(200),
    ip VARCHAR(200)
);
TABLE
        );
    }
}
