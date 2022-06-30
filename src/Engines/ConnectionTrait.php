<?php

declare(strict_types=1);

namespace Driver\Engines;

use PDO;

trait ConnectionTrait
{
    private ?ReconnectingPDO $connection = null;

    public function getConnection(): ReconnectingPDO
    {
        if (!$this->connection) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->connection = new ReconnectingPDO($this->getDSN(), $this->getUser(), $this->getPassword(), $options);
        }

        return $this->connection;
    }
}
