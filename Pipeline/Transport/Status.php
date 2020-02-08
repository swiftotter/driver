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
 * @copyright SwiftOtter Studios, 11/5/16
 * @package default
 **/

namespace Driver\Pipeline\Transport;

class Status
{
    private $node;
    private $message;
    private $isError;

    public function __construct($node, $message, $isError = false)
    {
        $this->node = $node;
        $this->message = $message;
        $this->isError = $isError;
    }

    public function isError()
    {
        return (bool)$this->isError;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getMessage()
    {
        return $this->message;
    }
}