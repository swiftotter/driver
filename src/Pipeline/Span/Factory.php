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
 * @copyright SwiftOtter Studios, 10/29/16
 * @package default
 **/

namespace Driver\Pipeline\Span;

use DI\Container;
use Driver\Pipeline\Master;
use Driver\System\Configuration;
use Driver\System\Factory\FactoryInterface;

class Factory
{
    private $configuration;
    private $container;
    private $type;

    public function __construct(Configuration $configuration, Container $container, $type)
    {
        $this->configuration = $configuration;
        $this->container = $container;
        $this->type = $type;
    }

    /**
     * @param $pipelineName
     * @return SpanInterface
     */
    public function create($pipelineName)
    {
        return $this->container->make($this->type, [ 'list' => $this->getNamedPipeline($pipelineName) ]);
    }

    protected function getDefaultPipeline()
    {
        return $this->getNamedPipeline(Master::DEFAULT_NODE);
    }

    protected function pipelineExists($name)
    {
        return is_array($this->configuration->getNode("pipelines/{$name}"));
    }

    protected function getNamedPipeline($name)
    {
        if ($this->pipelineExists($name)) {
            return $this->configuration->getNode("pipelines/{$name}");
        } else {
            throw new \Driver\Pipeline\Exception\PipeLineNotFound();
        }
    }
}