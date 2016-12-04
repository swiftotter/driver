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
 * @copyright SwiftOtter Studios, 11/19/16
 * @package default
 **/

namespace Driver\Engines\MySql;

use Driver\Engines\ConnectionTrait;
use Driver\Engines\ConnectionInterface;

class Connection implements ConnectionInterface
{
    use ConnectionTrait;

    private $configuration;

    public function __construct(\Driver\System\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getDSN()
    {
        return "mysql:host={$this->getHost()};dbname={$this->getDatabase()};port={$this->getPort()};charset={$this->getCharset()}";
    }

    public function getCharset()
    {
        if ($charset = $this->getValue('charset', false)) {
            return $charset;
        } else {
            return 'utf8';
        }
    }

    public function getHost()
    {
        if ($host = $this->getValue('host', false)) {
            return $host;
        } else {
            return 'localhost';
        }
    }

    public function getPort()
    {
        if ($port = $this->getValue('port', false)) {
            return $port;
        } else {
            return '3306';
        }
    }

    public function getDatabase()
    {
        return $this->getValue('database', true);
    }

    public function getUser()
    {
        return $this->getValue('user', true);
    }

    public function getPassword()
    {
        return $this->getValue('password', true);
    }

    private function getValue($key, $required)
    {
        $value = $this->configuration->getNode("connections/mysql/{$key}");
        if (is_array($value) && $required) {
            throw new \Exception("{$key} is not set. Please set it in a configuration file.");
        }

        return $value;
    }
}