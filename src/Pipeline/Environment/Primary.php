<?php

declare(strict_types=1);

namespace Driver\Pipeline\Environment;

use Driver\Engines\MySql\Sandbox\Utilities;

class Primary implements EnvironmentInterface
{
    private string $name;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private Utilities $utilities;
    /** @var string[] */
    private array $ignoredTables = [];
    /** @var string[] */
    private array $emptyTables = [];

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(string $name, array $properties, Utilities $utilities)
    {
        $this->name = $name;
        $this->properties = $properties;
        $this->utilities = $utilities;
    }

    /**
     * @return string[]
     */
    public function getOnlyForPipeline(): array
    {
        return $this->properties['only_for_pipeline'] ?? [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getIgnoredTables(): array
    {
        if (!$this->ignoredTables) {
            $this->ignoredTables = $this->properties['ignored_tables'] ?? [];
        }

        return $this->ignoredTables;
    }

    /**
     * @return string[]
     */
    public function getEmptyTables(): array
    {
        if (!$this->emptyTables) {
            if (isset($this->properties['empty_tables'])) {
                $this->emptyTables = $this->properties['empty_tables'];
            } else {
                $this->emptyTables = [];
            }
        }
        return $this->emptyTables ?? [];
    }

    public function getSort(): int
    {
        return isset($this->properties['sort'])
            ? (int)$this->properties['sort']
            : 1000;
    }

    /**
     * @return string[]
     */
    public function getTransformations(): array
    {
        return isset($this->properties['transformations'])
            ? $this->flattenTransformations($this->properties['transformations'])
            : [];
    }

    /**
     * @param array<string, string[]> $input
     * @return string[]
     */
    private function flattenTransformations(array $input): array
    {
        $output = [];
        array_walk($input, function ($transformations, $tableName) use (&$output): void {
            $output = array_merge(
                $output,
                $this->parseVariables($this->utilities->tableName($tableName), $transformations)
            );
        });

        return $output;
    }

    /**
     * @param string[] $input
     * @return string[]
     */
    private function parseVariables(string $tableName, array $input): array
    {
        return array_map(function ($query) use ($tableName) {
            return str_replace("{{table_name}}", $tableName, $query);
        }, $input);
    }
}
