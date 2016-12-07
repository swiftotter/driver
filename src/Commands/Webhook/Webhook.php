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
 * @copyright SwiftOtter Studios, 12/6/16
 * @package default
 **/

namespace Driver\Commands\Webhook;

use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;

class Webhook implements WebhookInterface
{
    private $configuration;
    private $logger;

    public function __construct(Configuration $configuration, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function call($webhookUrl, $data, $method)
    {
        $client = new \GuzzleHttp\Client();
        $options = $this->getAuth([]);
        $options = $this->getData($options, $data, $method);

        try {
            $client->request($method, $webhookUrl, $options);
        } catch (\Exception $ex) {
            $this->logger->alert($ex->getMessage());
        }
    }

    public function post($webhookUrl, $data)
    {
        $this->call($webhookUrl, $data, 'POST');
    }

    public function get($webhookUrl, $data)
    {
        $this->call($webhookUrl, $data, 'GET');
    }

    private function getData($options, $data, $method)
    {
        if (!count($data)) {
            return $options;
        }

        if ($method === "GET") {
            return array_merge($options, [ 'query_string' => http_build_query($data)]);
        } else {
            return array_merge($options, [ 'json' => $data]);
        }
    }

    private function getAuth($options)
    {
        $auth = $this->configuration->getNode('connections/webhook/auth');
        $node = [];

        if (count($auth)) {
            $node['auth'] = [ $auth['user'], $auth['password'] ];
        }

        return array_merge($options, $node);
    }

}