#!/usr/bin/env php
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


require __DIR__.'/../vendor/autoload.php';

use Driver\System\Application;
use DI\ContainerBuilder;
use Driver\System\DependencyConfig;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dependencyConfig = new DependencyConfig;

$containerBuilder = new ContainerBuilder;
$containerBuilder->addDefinitions($dependencyConfig->get());
$container = $containerBuilder->build();

$application = $container->get('Driver\System\Application');
$application->run();