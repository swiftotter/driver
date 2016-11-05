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

namespace Driver\Pipes;

use Driver\Pipes\Exception\PipeSetNotFound as PipeSetNotFoundException;
use Driver\Pipes\Set\Factory as PipeSetFactory;
use Driver\Pipes\Transport\Factory as TransportFactory;
use Driver\System\Configuration;

class Master
{
    const EMPTY_NODE = 'empty';
    const DEFAULT_NODE = 'default';
    protected $configuration;
    protected $pipeSetFactory;
    protected $transportFactory;

    public function __construct(Configuration $configuration, PipeSetFactory $pipeSetFactory, TransportFactory $transportFactory)
    {
        $this->configuration = $configuration;
        $this->pipeSetFactory = $pipeSetFactory;
        $this->transportFactory = $transportFactory;
    }

    public function runDefault()
    {
        $this->run(self::DEFAULT_NODE);
    }

    public function run($set)
    {
        $pipeSet = $this->pipeSetFactory->create($set);
        $pipeSet($set);
    }

    protected function createTransport()
    {
        return $this->transportFactory->create(self::DEFAULT_NODE);
    }
}