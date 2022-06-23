<?php

declare(strict_types=1);

namespace Driver\System;

use Driver\System\Configuration\FileCollector;
use Driver\System\Configuration\FileLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function array_keys;
use function array_merge;
use function array_reduce;
use function array_walk;
use function count;
use function explode;
use function is_array;
use function is_int;
use function is_string;

class Configuration
{
    private FileCollector $fileCollector;
    private FileLoader $loader;
    private array $nodes = ['pipelines' => []];
    private array $files = [];

    public function __construct(FileCollector $fileCollector, FileLoader $fileLoader)
    {
        $this->fileCollector = $fileCollector;
        $this->loader = $fileLoader;
    }

    public function getNodes(): array
    {
        if (!count($this->files)) {
            foreach ($this->fileCollector->get() as $file) {
                $this->loadConfigurationFor($file);
            };
        }

        return $this->nodes;
    }

    /**
     * @return mixed
     */
    public function getNode($node)
    {
        $path = explode('/', $node);
        $nodes = $this->getNodes();

        return array_reduce($path, function($nodes, $item) {
            return $nodes[$item] ?? null;
        }, $nodes);
    }

    public function getNodeString($node): string
    {
        $value = $this->getNode($node);

        return is_string($value) ? $value : '';
    }

    private function loadConfigurationFor($file): void
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

                    $this->nodes = $this->recursiveMerge($this->nodes, $contents);
                    $this->nodes['pipelines'] = $this->mergePipelines($pipelines);
                }
            } catch (ParseException $e) {
                $this->files[$file] = [];
                throw $e;
            }
        }
    }

    private function recursiveMerge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]) && !is_int($key)) {
                $merged[$key] = $this->recursiveMerge($merged[$key], $value);
            } else if (is_int($key)) {
                $merged[] = $value;
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Special handling for pipelines as they don't exactly follow the key/value pattern.
     */
    private function mergePipelines(array $new): array
    {
        if (!isset($this->nodes['pipelines']) || !count($this->nodes['pipelines'])) {
            return $new;
        }

        if (!count($new)) {
            return $this->nodes['pipelines'];
        }

        /** @var array $pipelinesKeys */
        $pipelinesKeys = array_merge(
            array_keys($this->nodes['pipelines']),
            array_keys($new)
        );

        return array_reduce($pipelinesKeys, function($carry, $key) use ($new) {
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

    private function mergePipeline($existing, $new)
    {
        array_walk($new, function($value) use (&$existing) {
            $existing = $this->mergeStageIntoPipeline($existing, $value);
        });

        return $existing;
    }

    private function mergeStageIntoPipeline(array $existing, array $newStage): array
    {
        $matched = false;

        $output = array_reduce(
            array_keys($existing),
            function($carry, $existingKey) use ($existing, $newStage, &$matched) {
                $existingStage = $existing[$existingKey];
                $currentMatch = isset($newStage['name'])
                    && isset($existingStage['name'])
                    && $newStage['name'] == $existingStage['name'];
                $matched = $matched || $currentMatch;

                if (!$currentMatch) {
                    return $carry;
                }

                $carry[$existingKey] = array_merge($existingStage, $newStage);
                $carry[$existingKey]['actions'] = array_merge($existingStage['actions'], $newStage['actions']);

                return $carry;
            },
            $existing
        );

        if (!$matched) {
            $output[] = $newStage;
        }

        return $output;
    }
}
