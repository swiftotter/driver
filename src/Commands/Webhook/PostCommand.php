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
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use JmesPath\Env;
use Symfony\Component\Console\Command\Command;

class PostCommand extends Command implements CommandInterface
{
    private $configuration;
    private $webhook;
    private $properties;

    public function __construct(Configuration $configuration, Webhook $webhook, array $properties = [])
    {
        $this->configuration = $configuration;
        $this->webhook = $webhook;
        $this->properties = $properties;

        parent::__construct('webhook-post-command');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $url = $this->configuration->getNode('connections/webhooks/post-url');

        if (!is_array($url) && $url) {
            $this->webhook->post($url, $transport->getAllData());
        }

        if (isset($this->properties['url'])) {
            $this->webhook->post($this->properties['url'], $transport->getAllData());
        }

        return $transport->withStatus(new Status('webhook_postcommand', 'success'));
    }
}