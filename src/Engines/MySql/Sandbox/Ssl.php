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
 * @copyright SwiftOtter Studios, 11/25/16
 * @package default
 **/

namespace Driver\Engines\MySql\Sandbox;

class Ssl
{
    const RDS_CA_URL = 'https://s3.amazonaws.com/rds-downloads/rds-combined-ca-bundle.pem';
    const SYSTEM_PATH = '/tmp/rds-combined-ca-bundle.pem';

    public function getPath()
    {
        if (!file_exists(self::SYSTEM_PATH)) {
            $this->downloadTo(self::SYSTEM_PATH);
        }

        if (file_exists(self::SYSTEM_PATH)) {
            return self::SYSTEM_PATH;
        } else {
            return false;
        }
    }

    public function mergeOptions($input)
    {
        if ($path = $this->getPath()) {
            return array_merge($input, [ \PDO::MYSQL_ATTR_SSL_CA => $this->getPath() ]);
        } else {
            return $input;
        }
    }

    private function downloadTo($path)
    {
        file_put_contents($path, fopen(self::RDS_CA_URL, 'r'));
    }
}