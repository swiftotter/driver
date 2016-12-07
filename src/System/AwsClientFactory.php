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
 * @copyright SwiftOtter Studios, 12/7/16
 * @package default
 **/

namespace Driver\System;

use Aws\Rds\RdsClient;

class AwsClientFactory
{
    private $creator;

    public function __construct(callable $creator = null)
    {
        $this->creator = $creator;
    }

    public function create($serviceType, $arguments)
    {
        if (!$this->creator || !is_callable($this->creator)) {
            return $this->doCreate($serviceType, $arguments);
        } else {
            return $this->creator->__invoke($serviceType, $arguments);
        }
    }

    private function doCreate($serviceType, $arguments)
    {
        if (strpos($serviceType, "\\") !== false) {
            $type = $serviceType;
        } else {
            $type = '\\Aws\\' . $serviceType . '\\' . $serviceType . 'Client';
        }

        return new $type($arguments);
    }
}