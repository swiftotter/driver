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

use Driver\Engines\RemoteConnectionInterface;
use Driver\Engines\ConnectionTrait;
use Driver\System\Configuration;

class Connection implements RemoteConnectionInterface
{
    use ConnectionTrait;

    private Configuration $configuration;
    private Sandbox $sandbox;
    private Ssl $ssl;

    public function __construct(Configuration $configuration, Sandbox $sandbox, Ssl $ssl)
    {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->ssl = $ssl;
    }

    public function useSsl(): bool
    {
        return true;
    }

    public function test($onFailure): void
    {
        try {
            $this->getConnection();
        } catch (\Exception $ex) {
            if (is_callable($onFailure)) {
                $onFailure($this);
            }
        }
    }

    public function isAvailable(): bool
    {
        try {
            $this->getConnection();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function authorizeIp(): void
    {
        $this->sandbox->authorizeIp();
    }

    public function getCharset(): string
    {
        if ($charset = $this->configuration->getNode('connections/mysql/charset')) {
            return $charset;
        } else {
            return 'utf8';
        }
    }

    public function getDSN(): string
    {
        return "mysql:host={$this->getHost()};dbname={$this->getDatabase()};port={$this->getPort()};charset={$this->getCharset()}";
    }

    public function getHost(): string
    {
        return $this->sandbox->getEndpointAddress();
    }

    public function getPort(): string
    {
        return $this->sandbox->getEndpointPort();
    }

    public function getDatabase(): string
    {
        return $this->sandbox->getDBName();
    }

    public function getUser(): string
    {
        return $this->sandbox->getUsername();
    }

    public function getPassword(): string
    {
        return $this->sandbox->getPassword();
    }

    public function getPreserve(): array
    {
        return [];
    }
}
