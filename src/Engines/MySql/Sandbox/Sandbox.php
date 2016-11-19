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

namespace Driver\Engines\MySql\Sandbox;

use Aws\Rds\RdsClient;
use Driver\System\Configuration;

class Sandbox
{
    private $configuration;
    private $instance;
    private $initialized;

    private $dbName;
    private $identifier;
    private $username;
    private $password;

    public function __construct(Configuration $configuration, $disableInstantiation = false)
    {
        $this->configuration = $configuration;
        if (!$disableInstantiation) {
            $this->init();
        }
    }

    public function init()
    {
        if ($this->initialized) {
            return false;
        }

        $this->initialized = true;

        $client = new RdsClient([
            'credentials' => [
                'key' => $this->configuration->getNode('connections/rds/key'),
                'secret' => $this->configuration->getNode('connections/rds/secret') ],
            'region' => $this->configuration->getNode('connections/rds/region'),
            'version' => '2014-10-31'
        ]);

        $this->instance = $client->createDBInstance([
            'DBName' => 'd' . $this->getDBName(),
            'DBInstanceIdentifier' => $this->getIdentifier(),
            'AllocatedStorage' => 5,
            'DBInstanceClass' => $this->configuration->getNode('connections/rds/instance-type'),
            'Engine' => 'MySQL',
            'MasterUsername' => 'u' . $this->getUsername(),
            'MasterUserPassword' => $this->getPassword()
        ]);

        return true;
    }

    private function getDBName()
    {
        if (!$this->dbName) {
            $this->dbName = $this->getRandomString(12);
        }

        return $this->dbName;
    }

    private function getIdentifier()
    {
        if (!$this->identifier) {
            $this->identifier = 'driver-upload-' . $this->getRandomString(6);
        }

        return $this->identifier;
    }

    private function getUsername()
    {
        if (!$this->username) {
            $this->username = $this->getRandomString(12);
        }

        return $this->username;
    }

    private function getPassword()
    {
        if (!$this->password) {
            $this->password = $this->getRandomString(30);
        }

        return $this->password;
    }

    private function getRandomString($length)
    {
        return bin2hex(openssl_random_pseudo_bytes(round($length/2)));
    }
}