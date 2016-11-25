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

namespace Driver\System;

use DI;
use Driver\Pipes\Stage;
use Driver\Pipes\Set;
use Driver\Pipes\Transport\Factory as TransportFactory;
use Driver\Pipes\Transport\Primary as TransportPrimary;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Logs\Primary;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\ApplicationTester as ConsoleApplicationTester;

class DependencyConfig
{
    public function get()
    {
        return [
            LoggerInterface::class => DI\Factory(function() {
                return new Primary();
            }),
            Stage\StageInterface::class => DI\factory([Stage\Primary::class, 'create']),
            Stage\Factory::class => DI\object()->constructorParameter('type', Stage\Primary::class),
            Set\SetInterface::class => DI\factory([Set\Primary::class, 'create']),
            Set\Factory::class => DI\object()->constructorParameter('type', Set\Primary::class),
            TransportFactory::class => DI\object()->constructorParameter('type', TransportPrimary::class)
        ];
    }

    public function getForTests()
    {
        return array_merge(
            $this->get(),
            [ ]
        );
    }
}