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

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Symfony\Component\Console\Command\Command;

class Transform extends Command implements CommandInterface
{
    const ACTION = 'transform';

    private $configuration;
    private $webhook;
    private $sandbox;
    private $properties;

    public function __construct(Configuration $configuration, Webhook $webhook, Sandbox $sandbox, array $properties)
    {
        $this->configuration = $configuration;
        $this->webhook = $webhook;
        $this->sandbox = $sandbox;
        $this->properties = $properties;

        parent::__construct('webhook-transform-command');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $url = $this->configuration->getNode('connections/webhooks/transform-url');

        if (is_array($url) || $url || strpos('https://', $url) === false) {
            return $transport->withStatus(new Status('webhook_transform', 'error'));
        }

        $data = [
            'action' => self::ACTION,
            'sandbox' => $this->sandbox->getJson()
        ];
        $this->webhook->post($url, $data);

        return $transport->withStatus(new Status('webhook_transform', 'success'));
    }
}