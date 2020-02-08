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
 * @copyright SwiftOtter Studios, 10/15/16
 * @package default
 **/

namespace Driver\Tests\Unit\System\Configuration;

use Driver\System\Configuration\YamlLoader;

class YamlLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testAllowedFiles()
    {
        $configuration = new YamlLoader();

        $method = new \ReflectionMethod($configuration, 'isAllowedFile');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($configuration, '/var/www/config/pipelines.yaml'));
        $this->assertTrue($method->invoke($configuration, '/var/www/config.d/commands.yaml'));
        $this->assertTrue($method->invoke($configuration, '/var/www/config.d/connections.yaml'));

        $this->assertFalse($method->invoke($configuration, '/var/www/configuration/chain.yaml'));
        $this->assertFalse($method->invoke($configuration, '/var/www/test/chain.yaml'));
        $this->assertFalse($method->invoke($configuration, '/var/www/config/input.yaml'));
        $this->assertFalse($method->invoke($configuration, '/var/www/config.d/bad-file.yaml'));
    }

    public function testGetYamlFilesReturnsArray()
    {
        $configuration = new YamlLoader();

        $method = new \ReflectionMethod($configuration, 'get');
        $method->setAccessible(true);

        $count = 0;
        $files = [];
        foreach ($method->invoke($configuration) as $file) {
            $count++;
            $files[] = $file;
        }

        $this->assertGreaterThanOrEqual(1, $count);
    }
}