<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 2/8/20
 * @website https://swiftotter.com
 **/

namespace Driver\Engines\MySql\Transformation\Anonymize;

use Driver\Engines\RemoteConnectionInterface;
use Driver\System\Configuration;

class Seed
{
    const FAKE_USER_TABLE = 'fake_users';

    private RemoteConnectionInterface $connection;
    private Configuration $configuration;

    public function __construct(Configuration $configuration, RemoteConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
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
        }
    }

    public function destroy()
    {
        $this->clean();
    }

    private function clean()
    {
        $this->connection->getConnection()->query('DROP TABLE IF EXISTS ' . self::FAKE_USER_TABLE);
    }

    private function createTable()
    {
        $table = self::FAKE_USER_TABLE;

        $this->connection->getConnection()->query(<<<TABLE
CREATE TABLE ${table} (
    firstname VARCHAR(200),
    lastname VARCHAR(200),
    company VARCHAR(200),
    street VARCHAR(200),
    city VARCHAR(200),
    region VARCHAR(200),
    region_id VARCHAR(10),
    postcode VARCHAR(200),
    country_id VARCHAR(2),
    phone VARCHAR(200),
    ip VARCHAR(200)
);
TABLE
        );
    }
}
