<?php

declare(strict_types=1);

namespace Driver\Engines;

use PDO;

trait ConnectionTrait
{
    private ?PersistentPDO $connection = null;

    public function getConnection(): PersistentPDO
    {
        if (!$this->connection) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->connection = new PersistentPDO($this->getDSN(), $this->getUser(), $this->getPassword(), $options);
        }

        return $this->connection;
    }
}
