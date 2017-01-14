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
 * @copyright SwiftOtter Studios, 11/5/16
 * @package default
 **/

namespace Driver\Pipeline\Span;

use Driver\Pipeline\Environment\Factory as EnvironmentFactory;
use Driver\Pipeline\Environment\Manager;
use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\System\YamlFormatter;

interface SpanInterface
{
    public function __construct(array $list, StageFactory $stageFactory, YamlFormatter $yamlFormatter, Manager $environmentManager, EnvironmentFactory $environmentFactory);

    public function __invoke(\Driver\Pipeline\Transport\TransportInterface $transport);

    public function cleanup(\Driver\Pipeline\Transport\TransportInterface $transport);
}