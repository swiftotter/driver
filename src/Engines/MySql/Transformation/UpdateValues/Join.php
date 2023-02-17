<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Transformation\UpdateValues;

class Join
{
    private string $table;
    private string $alias;
    private string $on;

    public function __construct(string $table, string $alias, string $on)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->on = $on;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getOn(): string
    {
        return $this->on;
    }
}
