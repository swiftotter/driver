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
    private $prefix = false;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection->getConnection();
    }

    public function tableName($tableName)
    {
        return $this->getPrefix() . $tableName;
    }

    public function tableExists($tableName)
    {
        try {
            $result = $this->connection->query("SELECT 1 FROM $tableName LIMIT 1");
        } catch (\Exception $e) {
            return false;
        }

        return $result !== false;
    }

    public function clearTable($tableName)
    {
        try {
            $this->connection->beginTransaction();

            if ($this->tableExists($tableName)) {
                $this->connection->query("set foreign_key_checks=0");
                $this->connection->query("TRUNCATE TABLE {$tableName}");
            }

            $this->connection->commit();
        } catch (\Exception $ex) {
            $this->connection->rollBack();
        } finally {
            $this->connection->query("set foreign_key_checks=1");
        }
    }

    private function getPrefix()
    {
        if ($this->prefix === false) {
            $testTable = 'core_config_data';
            $result = $this->connection->query('SHOW TABLES;');

            $fullTableName = array_filter($result->fetchColumn(0), function ($tableName) use ($testTable) {
                return strpos($tableName, $testTable) !== false;
            });

            list($prefix) = explode($testTable, $fullTableName);
            $this->prefix = $prefix;
        }

        return $this->prefix;
    }
}