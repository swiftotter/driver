<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Transformation\UpdateValues;

use InvalidArgumentException;

use function implode;

class QueryBuilder
{
    /**
     * @param Value[] $values
     * @param Join[] $joins
     */
    public function build(string $table, array $values = [], array $joins = []): string
    {
        if (empty($values)) {
            return '';
        }

        $query = "UPDATE ${table}";
        foreach ($joins as $join) {
            if (!$join instanceof Join) {
                throw new InvalidArgumentException('Join object expected.');
            }
            $query .= ' INNER JOIN ' . $join->getTable() . ' ' . $join->getAlias() . ' ON ' . $join->getOn();
        }
        $query .= ' SET ';
        $set = [];
        foreach ($values as $value) {
            if (!$value instanceof Value) {
                throw new InvalidArgumentException('Value object expected.');
            }
            $set[] = $table . '.' . $value->getField() . ' = ' . $value->getValue();
        }
        $query .= implode(', ', $set);

        return $query;
    }
}
