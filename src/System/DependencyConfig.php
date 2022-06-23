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
 * @copyright SwiftOtter Studios, 10/8/16
 * @package default
 **/

declare(strict_types=1);

namespace Driver\System;

use DI;
use DI\Definition\Helper\DefinitionHelper;
use Driver\Engines\LocalConnectionInterface;
use Driver\Engines\MySql\Sandbox\Connection;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment;
use Driver\Pipeline\Stage;
use Driver\Pipeline\Span;
use Driver\Pipeline\Transport\Factory as TransportFactory;
use Driver\Pipeline\Transport\Primary as TransportPrimary;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Logs\Primary;

class DependencyConfig
{
    private bool $isDebug;

    public function __construct(bool $isDebug = false)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * @return <string, DefinitionHelper>
     */
    public function get(): array
    {
        return [
            LoggerInterface::class => DI\Factory(function() {
                return new Primary();
            }),
            Environment\EnvironmentInterface::class => DI\factory([Environment\Primary::class, 'create']),
            Environment\Factory::class => DI\autowire()->constructorParameter('type', Environment\Primary::class),
            Stage\StageInterface::class => DI\factory([Stage\Primary::class, 'create']),
            Stage\Factory::class => DI\autowire()->constructorParameter('type', Stage\Primary::class),
            Span\SpanInterface::class => DI\factory([Span\Primary::class, 'create']),
            Span\Factory::class => DI\autowire()->constructorParameter('type', Span\Primary::class),
            TransportFactory::class => DI\autowire()->constructorParameter('type', TransportPrimary::class),
            DebugMode::class => DI\create()->constructor($this->isDebug),
            RemoteConnectionInterface::class => DI\autowire(
                $this->isDebug ? DebugExternalConnection::class : Connection::class
            ),
            LocalConnectionInterface::class => DI\autowire(
                \Driver\System\LocalConnectionLoader::class
            )
        ];
    }

    /**
     * @return <string, DefinitionHelper>
     */
    public function getForTests(): array
    {
        return array_merge(
            $this->get(),
            [ ]
        );
    }
}
