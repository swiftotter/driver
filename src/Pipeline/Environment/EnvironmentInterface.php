<?php

declare(strict_types=1);

namespace Driver\Pipeline\Environment;

interface EnvironmentInterface
{
    /**
     * @return string[]
     */
    public function getOnlyForPipeline(): array;

    public function getName(): string;

    /**
     * @return string[]
     */
    public function getTransformations(): array;

    public function getSort(): int;

    /**
     * @return string[]
     */
    public function getIgnoredTables(): array;

    /**
     * @return string[]
     */
    public function getEmptyTables(): array;
}
