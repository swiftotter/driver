<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 12/5/16
 * @package default
 **/

namespace Driver\Engines\MySql\Sandbox;

class Utilities
{
    private $connection;
    private $cachedTables = false;

    public function __construct(\Driver\Engines\MySql\Connection $connection)
    {
        $this->connection = $connection;
    }

    public function tableExists($tableName)
    {
        try {
            $result = $this->connection->getConnection()->query("SELECT 1 FROM $tableName LIMIT 1");
        } catch (\Exception $e) {
            return false;
        }

        return $result !== false;
    }

//    public function clearTable($tableName)
//    {
//        $connection = $this->connection->getConnection();
//        try {
//            $connection->beginTransaction();
//
//            if ($this->tableExists($tableName)) {
//                $connection->query("set foreign_key_checks=0");
//                $connection->query("TRUNCATE TABLE {$tableName}");
//            }
//
//            $connection->commit();
//        } catch (\Exception $ex) {
//            $connection->rollBack();
//        } finally {
//            $connection->query("set foreign_key_checks=1");
//        }
//    }

    public function tableName($tableName)
    {
        $fullTableName = array_reduce($this->getTables(), function ($carry, $sourceTableName) use ($tableName) {
            if (strlen($sourceTableName) < strlen($tableName)) {
                return $carry;
            }

            if ($sourceTableName == $tableName) {
                return $tableName;
            }

            if (substr_compare($sourceTableName, $tableName, strlen($sourceTableName) - strlen($tableName), strlen($tableName)) === 0) {
                return $sourceTableName;
            }

            return $carry;
        }, '');

        return $fullTableName;
    }

    private function getTables()
    {
        if ($this->cachedTables === false) {
            $result = $this->connection->getConnection()->query('SHOW TABLES;');

            $this->cachedTables = $result->fetchAll(\PDO::FETCH_COLUMN, 0);
        }

        return $this->cachedTables;
    }
}