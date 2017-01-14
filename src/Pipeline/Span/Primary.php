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

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Environment\Factory as EnvironmentFactory;
use Driver\Pipeline\Environment\Manager as EnvironmentManager;
use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\Pipeline\Stage\StageInterface;
use Driver\Pipeline\Transport\Status;
use Driver\System\YamlFormatter;
use Haystack\HArray;

class Primary implements SpanInterface
{
    const PIPE_SET_NODE = 'parent';
    const UNSET_ENVIRONMENT = 'default';

    private $stages;
    private $stageFactory;
    private $environmentManager;
    private $environmentFactory;

    public function __construct(array $list, StageFactory $stageFactory, YamlFormatter $yamlFormatter, EnvironmentManager $environmentManager, EnvironmentFactory $environmentFactory)
    {
        $this->stageFactory = $stageFactory;
        $this->environmentManager = $environmentManager;
        $this->environmentFactory = $environmentFactory;

        $this->stages = $this->generateStageMap($yamlFormatter->extractSpanList($list));
    }

    public function __invoke(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        $stages = !$testMode ? $this->stages : [];

        (new HArray($stages))
            ->walk(function(StageInterface $stage) use ($transport){
                return $this->verifyTransport($stage($transport), $stage->getName());
            });

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    public function cleanup(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        $stages = !$testMode ? $this->stages : [];

        (new HArray($stages))
            ->walk(function(StageInterface $stage) use ($transport){
                return $stage->cleanup($transport);
            });

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'cleaned'));
    }

    private function generateStageMap($list)
    {
        $stages = $this->mapToStageObjects($this->sortStages($this->filterStages($list)));

        $output = new \ArrayIterator();
        $defaultEnvironment = new \ArrayIterator([ $this->environmentFactory->createDefault() ]);

        array_walk($stages, function(StageInterface $stage) use (&$output, $defaultEnvironment) {
            if ($stage->isRepeatable()) {
                $output = $this->repeatForEnvironments($stage, $output, $this->environmentManager->getRunFor());
            } else {
                $output = $this->repeatForEnvironments($stage, $output, $defaultEnvironment);
            }
        });

        return $output->getArrayCopy();
    }

    private function sortStages(array $stages)
    {
        uasort($stages, function($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $stages;
    }

    private function filterStages(array $stages)
    {
        return (new HArray($stages))
            ->filter(function($actions) {
                return count($actions) > 0;
            })->toArray();
    }

    private function mapToStageObjects(array $stages)
    {
        return array_filter(array_map(function($stage, $name) {
            if (isset($stage['actions'])) {
                return $this->stageFactory->create($stage['actions'], $name);
            } else {
                return null;
            }
        }, array_values($stages), array_keys($stages)));
    }

    private function repeatForEnvironments(StageInterface $stage, \ArrayIterator $output, \ArrayIterator $environments)
    {
        return array_reduce($environments->getArrayCopy(), function(\ArrayIterator $input, EnvironmentInterface $environment) use ($stage) {
            $output = new \ArrayIterator($input->getArrayCopy());
            $output->append($stage->withEnvironment($environment));

            return $output;
        }, $output);
    }

    private function verifyTransport(\Driver\Pipeline\Transport\TransportInterface $transport, $lastCommand)
    {
        if (!$transport) {
            throw new \Exception('No Transport object was returned from the last command executed: ' . $lastCommand);
        }

        return $transport;
    }
}