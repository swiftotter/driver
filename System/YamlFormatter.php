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
 * @copyright SwiftOtter Studios, 11/25/16
 * @package default
 **/

namespace Driver\System;

class YamlFormatter
{
    /**
     * Converts an array like:
     * 0 => [
     *      100 => "test"
     * ]
     * TO:
     * [
     *      100 => "test"
     * ]
     * @param array $input
     */
    public function extractToAssociativeArray($input)
    {
        $output = array_reduce($input, function($commands, array $item) {
            array_walk($item, function($values, $name) use (&$commands) {
                if (isset($commands[$name]) && !is_array($commands[$name]) && is_array($values)) {
                    $values[] = $values;
                    $commands[$name] = $values;
                } else if (isset($commands[$name]) && is_array($commands[$name]) && is_array($values)) {
                    $commands[$name] = array_merge($commands[$name], $values);
                } else if (isset($commands[$name])) { // if $commands[$name] is set, turn it into an array
                    $commands[$name] = [ $commands[$name], $values ];
                } else { // if $commands[$name] is not set
                    $commands[$name] = $values;
                }
            });

            return $commands;
        }, []);

        return $output;
    }

    public function extractSpanList($input)
    {
        $output = array_reduce($input, function($commands, array $item) {
            if (isset($item['name'])) {
                $name = $item['name'];
                unset($item['name']);

//                if (isset($item['stages'])) {
//                    $item['stages'] = $this->extractToAssociativeArray($item['stages']);
//                }

                if (isset($commands[$name])) {
                    $commands[$name] = array_merge_recursive($commands[$name], $item);
                } else {
                    $commands[$name] = $item;
                }
            }

            return $commands;
        }, []);

        return $output;
    }
}