<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\System\LocalConnectionLoader;

class Utilities
{
    private LocalConnectionLoader $connection;
    /** @var string[]|null */
    private ?array $cachedTables = null;

    public function __construct(LocalConnectionLoader $connection)
    {
        $this->connection = $connection;
    }

    public function tableExists(string $tableName): bool
    {
        try {
            $result = $this->connection->getConnection()->query("SELECT 1 FROM $tableName LIMIT 1");
        } catch (\Exception $e) {
            return false;
        }

        return $result !== false;
    }

    public function tableName(string $tableName): string
    {
        return array_reduce($this->getTables(), function ($carry, $sourceTableName) use ($tableName) {
            if (strlen($sourceTableName) < strlen($tableName)) {
                return $carry;
            }

            if ($sourceTableName == $tableName) {
                return $tableName;
            }

            if (
                substr_compare(
                    $sourceTableName,
                    $tableName,
                    strlen($sourceTableName) - strlen($tableName),
                    strlen($tableName)
                ) === 0
            ) {
                return $sourceTableName;
            }

            return $carry;
        }, '');
    }

    /**
     * @return array|string[]
     */
    private function getTables(): array
    {
        if ($this->cachedTables === null) {
            $result = $this->connection->getConnection()->query('SHOW TABLES;');
            $this->cachedTables = $result->fetchAll(\PDO::FETCH_COLUMN, 0) ?: [];
        }

        return $this->cachedTables;
    }
}
