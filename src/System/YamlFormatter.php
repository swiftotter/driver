<?php

declare(strict_types=1);

namespace Driver\System;

class YamlFormatter
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function extractSpanList(array $input): array
    {
        return array_reduce($input, function ($commands, array $item) {
            if (isset($item['name'])) {
                $name = $item['name'];
                unset($item['name']);
                $commands[$name] = isset($commands[$name]) ? array_merge_recursive($commands[$name], $item) : $item;
            }

            return $commands;
        }, []);
    }
}
