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
            array_walk($item, function($name, $id) use (&$commands) {
                while (isset($commands[$id])) {
                    $id++;
                }
                $commands[$id] = $name;
            });

            return $commands;
        }, []);

        return $output;
    }
}