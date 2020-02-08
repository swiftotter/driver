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

use DI\ContainerBuilder;

class Entry
{
    static private $arguments;

    public static function go($arguments)
    {
        set_time_limit(0);

        if (!is_array($arguments)) {
            $arguments = [];
        }
        self::$arguments = $arguments;
        self::configureDebug();

        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions((new DependencyConfig)->get());
        $container = $containerBuilder->build();

        $application = $container->get('Driver\System\Application');
        $application->run();
    }

    private static function configureDebug()
    {
        if (self::isDebug()) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
    }

    private static function isDebug()
    {
        return count(array_filter(self::$arguments, function($argument) {
            return strpos($argument, '--debug') !== false;
        })) > 0;
    }
}

