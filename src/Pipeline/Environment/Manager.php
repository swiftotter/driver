<?php

declare(strict_types=1);

namespace Driver\Pipeline\Environment;

use ArrayIterator;
use Driver\System\Configuration;

use function array_filter;
use function array_keys;
use function array_map;

class Manager
{
    private const ALL_ENVIRONMENTS = 'all';

    private ArrayIterator $runFor;
    private Factory $factory;
    private Configuration $configuration;
    private bool $hasCustomRunList = false;

    public function __construct(Factory $factory, Configuration $configuration)
    {
        $this->factory = $factory;
        $this->configuration = $configuration;
        $this->runFor = new ArrayIterator([]);
    }

    /**
     * @param string[]|string $environments
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    public function setRunFor($environments): void
    {
        if (!$environments || strtolower($environments) === self::ALL_ENVIRONMENTS) {
            $environmentList = $this->getAllEnvironments();
        } elseif (is_string($environments)) {
            $environmentList = array_filter(explode(',', $environments));
            array_walk($environmentList, 'trim');
            $this->hasCustomRunList = true;
        } else {
            $environmentList = $environments;
        }

        $this->runFor = new ArrayIterator($this->mapNamesToEnvironments($environmentList));
    }

    /**
     * @return string[]
     */
    public function getAllEnvironments(): array
    {
        return array_keys(array_filter($this->configuration->getNode('environments'), function ($value) {
            return !isset($value['empty']) || $value['empty'] == false;
        }));
    }

    /**
     * @param string[] $environments
     * @return EnvironmentInterface[]
     */
    private function mapNamesToEnvironments(array $environments): array
    {
        $mapped = array_map(function ($name) {
            return $this->factory->create($name);
        }, $environments);

        usort($mapped, function (EnvironmentInterface $a, EnvironmentInterface $b) {
            if ($a->getSort() == $b->getSort()) {
                return 0;
            }

            return ($a->getSort() < $b->getSort()) ? -1 : 1;
        });

        return $mapped;
    }

    public function getRunFor(): ArrayIterator
    {
        return $this->runFor;
    }

    public function hasCustomRunList(): bool
    {
        return $this->hasCustomRunList;
    }
}
