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

use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\Pipeline\Transport\Status;
use Driver\System\YamlFormatter;
use Haystack\HArray;

class Primary implements SpanInterface
{
    const PIPE_SET_NODE = 'parent';

    private $list;
    private $stageFactory;

    public function __construct(array $list, StageFactory $stageFactory, YamlFormatter $yamlFormatter)
    {
        $this->stageFactory = $stageFactory;
        $this->list = $yamlFormatter->extractToAssociativeArray($list);
    }

    public function __invoke(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        if ($testMode) {
            $this->list = [];
        }

        (new HArray($this->list))
            ->filter(function($actions) {
                return count($actions) > 0;
            })->walk(function($actions, $name) use ($transport){
                $stage = $this->stageFactory->create($actions);
                return $this->verifyTransport($stage($transport), $name);
            });

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    private function verifyTransport(\Driver\Pipeline\Transport\TransportInterface $transport, $lastCommand)
    {
        if (!$transport) {
            throw new \Exception('No Transport object was returned from the last command executed: ' . $lastCommand);
        }

        return $transport;
    }
}