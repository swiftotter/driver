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
use Driver\System\Configuration\YamlLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    /** @var YamlLoader $loader */
    protected $loader;

    protected $nodes = [
        'pipelines' => []
    ];

    protected $files = [];

    public function __construct(YamlLoader $loader)
    {
        $this->loader = $loader;
    }

    public function getNodes()
    {
        if (!count($this->files)) {
            $this->loadAllConfiguration();
        }

        return $this->nodes;
    }

    public function getNode($node)
    {
        $path = explode('/', $node);
        $nodes = $this->getNodes();

        return array_reduce($path, function($nodes, $item) {
            if (isset($nodes[$item])) {
                return $nodes[$item];
            } else {
                return [];
            }
        }, $nodes);
    }

    protected function loadConfigurationFor($file)
    {
        if (!isset($this->files[$file])) {
            try {
                $contents = Yaml::parse($this->loader->load($file));
                $this->files[$file] = $contents;
                if (is_array($contents)) {
                    $pipelines = [];
                    if (isset($contents['pipelines'])) {
                        $pipelines = $contents['pipelines'];
                        unset($contents['pipelines']);
                    }

                    $this->nodes = array_merge_recursive($this->nodes, $contents);
                    $this->nodes['pipelines'] = $this->mergePipelines($pipelines);
                }
            } catch (ParseException $e) {
                $this->files[$file] = [];
            }
        }

        return $this->files[$file];
    }

    /**
     * Special handling for pipelines as they don't exactly follow the key/value pattern.
     *
     * @param $input
     */
    protected function mergePipelines($new)
    {
        if (!isset($this->nodes['pipelines']) || !count($this->nodes['pipelines'])) {
            return $new;
        }

        if (!count($new)) {
            return $this->nodes['pipelines'];
        }

       return array_reduce(array_keys($this->nodes['pipelines']), function($carry, $key) use ($new) {
            $existing = $this->nodes['pipelines'];
            if (!isset($new[$key])) {
                $carry[$key] = $existing;
                return $carry;
            }
            if (!isset($existing[$key])) {
                $carry[$key] = $new[$key];
                return $carry;
            }

            $carry[$key] = $this->mergePipeline($existing[$key], $new[$key]);
            return $carry;
        }, []);
    }

    protected function mergePipeline($existing, $new)
    {
        array_walk($new, function($value) use (&$existing) {
            $existing = $this->mergeStageIntoPipeline($existing, $value);
        });

        return $existing;
    }

    protected function mergeStageIntoPipeline($existing, $newStage)
    {
        $matched = false;

        $output = array_reduce(array_keys($existing), function($carry, $existingKey) use ($existing, $newStage, &$matched) {
            $existingStage = $existing[$existingKey];
            $currentMatch = isset($newStage['name']) && isset($existingStage['name']) && $newStage['name'] == $existingStage['name'];
            $matched = $matched || $currentMatch;

            if (!$currentMatch) {
                return $carry;
            }

            $carry[$existingKey] = array_merge($existingStage, $newStage);
            $carry[$existingKey]['actions'] = array_merge($existingStage['actions'], $newStage['actions']);

            return $carry;
        }, $existing);

        if (!$matched) {
            $output[] = $newStage;
        }

        return $output;
    }

    protected function consolidateStage($existing, $new)
    {

    }

    protected function stripFileExtension($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    protected function loadAllConfiguration()
    {
        foreach ($this->loader->get() as $file) {
            $this->loadConfigurationFor((string)$file);
        };
    }
}