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

namespace Driver\Tests\Unit\System;

use DI\ContainerBuilder;
use Driver\System\Configuration;
use Driver\System\Configuration\YamlFilter;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected $testFile;

    public function setUp()
    {
        $this->testFile = realpath(__DIR__ . '../../../config/') . 'test.yaml';
        if (!file_exists($this->testFile)) {
            file_put_contents($this->testFile, "test:\n  value: unknown");
        }

        parent::setUp();
    }

    public function tearDown()
    {
        unlink($this->testFile);

        parent::tearDown();
    }

    protected function getConfiguration()
    {
        $configuration = new Configuration(new Configuration\YamlLoader());
        return $configuration;
    }

    protected function getConfigurationLoadedWithTestFile()
    {
        $configuration = $this->getConfiguration();
        $yamlLoader = new Configuration\YamlLoader();

        $filePath = $yamlLoader->getIndividual('test');

        $loaderMethod = new \ReflectionMethod($configuration, 'loadConfigurationFor');
        $loaderMethod->setAccessible(true);
        $output = $loaderMethod->invoke($configuration, reset($filePath));

        return [
            'output' => $output,
            'configuration' => $configuration
        ];
    }

    public function testGetAllNodesReturnsInformation()
    {
        $values = $this->getConfigurationLoadedWithTestFile();
        $configuration = $values['configuration'];

        $this->assertInternalType('array', $configuration->getNodes());
    }

    public function testGetNodeReturnsInformation()
    {
        $values = $this->getConfigurationLoadedWithTestFile();
        $configuration = $values['configuration'];

        $this->assertSame('unknown', $configuration->getNode('test/value'));
    }

    public function testGetAllConfigurationLoadsEverything()
    {
        $configuration = $this->getConfiguration();

        $filesProperty = new \ReflectionProperty($configuration, 'files');
        $filesProperty->setAccessible(true);

        $loaderMethod = new \ReflectionMethod($configuration, 'loadAllConfiguration');
        $loaderMethod->setAccessible(true);
        $loaderMethod->invoke($configuration);

        $this->assertTrue(count($filesProperty->getValue($configuration)) > 0);
    }

    public function testRemovesFileSuffix()
    {
        $configuration = $this->getConfiguration();

        $stripMethod = new \ReflectionMethod($configuration, 'stripFileExtension');
        $stripMethod->setAccessible(true);

        $this->assertEquals('test', $stripMethod->invoke($configuration, 'test.yaml'));
    }

    public function testGetConfigurationFileTestLoadsArrayValues()
    {
        $values = $this->getConfigurationLoadedWithTestFile();
        $configuration = $values['configuration'];
        $output = $values['output'];

        $this->assertEquals('unknown', $output['test']['value']);
        $this->assertTrue(count($configuration) > 0);
    }

    public function testGetConfigurationFileLoadsClassArrays()
    {
        $values = $this->getConfigurationLoadedWithTestFile();
        $configuration = $values['configuration'];

        $fileArray = new \ReflectionProperty($configuration, 'files');
        $fileArray->setAccessible(true);
        $nodeArray = new \ReflectionProperty($configuration, 'nodes');
        $nodeArray->setAccessible(true);
        $nodes = $nodeArray->getValue($configuration);

        $this->assertSame(1, count($fileArray->getValue($configuration)));
        $this->assertSame('unknown', $nodes['test']['value']);
    }

    public function testGetConfigurationFileLoadsData()
    {
        $yamlLoader = new Configuration\YamlLoader();
        $configuration = $this->getConfiguration();

        $files = $yamlLoader->get();

        $loaderMethod = new \ReflectionMethod($configuration, 'loadConfigurationFor');
        $loaderMethod->setAccessible(true);
        $configuration = $loaderMethod->invoke($configuration, reset($files));

        $this->assertTrue(count($configuration) > 0);
    }
}