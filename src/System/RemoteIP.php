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
 * @copyright SwiftOtter Studios, 11/24/16
 * @package default
 **/

namespace Driver\System;

use GuzzleHttp\Client;

class RemoteIP
{
    private $ip;

    public function getPublicIP()
    {
        if (!$this->ip) {
            $client = new Client();
            $response = $client->request('GET', 'https://api.ipify.org?format=json');
            $body = json_decode($response->getBody(), true);

            $this->ip = $body['ip'];
        }

        return $this->ip;
    }
}