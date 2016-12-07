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
 * @copyright SwiftOtter Studios, 12/6/16
 * @package default
 **/

namespace Driver\System\Configuration;

class SearchPath implements \Iterator
{
    const VENDOR_DIRECTORY = 'vendor';

    private $searchPaths;
    private $allowedFolders;
    private $position = 0;
    private $folders;


    public function __construct($directory = __DIR__, $allowedFolders)
    {
        $this->allowedFolders = $allowedFolders;
        $this->searchPaths = $this->format($directory);
        $this->folders = $this->findFolders($this->searchPaths);
    }

    private function format($directory)
    {
        $hasVendorDir = false;
        if (strpos($directory, self::VENDOR_DIRECTORY) !== false) {
            list($initial, $continue) = explode(self::VENDOR_DIRECTORY, $directory);
            $initial = [ $initial ];
            $continue = self::VENDOR_DIRECTORY . $continue;
            $hasVendorDir = true;
        } else {
            $topOfSearch = strlen(realpath($directory.'/../../../'));
            $initial = [ substr($directory, 0, $topOfSearch) ];
            $continue = substr($directory, $topOfSearch+1);
        }

        $paths = $this->merge(array_merge($initial, explode('/', $continue)));
        if ($hasVendorDir) {
            $paths = array_merge($paths, $this->loadVendorDirectories($initial[0]));
        }

        return $paths;
    }

    private function merge($directories)
    {
        $currentPath = '';

        return array_reduce($directories, function($acc, $path) use (&$currentPath) {
            $currentPath .= $path;
            if (substr($currentPath, -1) !== '/') {
                $currentPath .= '/';
            }
            $acc[] = $currentPath;
            return $acc;
        }, []);
    }

    private function loadVendorDirectories($path)
    {
        return glob($path . self::VENDOR_DIRECTORY . "/*/*/", GLOB_ONLYDIR);
    }

    private function findFolders($paths)
    {
        return array_reduce($paths, function($acc, $path) {
            $folders = array_filter($this->allowedFolders, function($folder) use ($path) {
                return file_exists($path . $folder);
            });

            return array_merge($acc, array_map(function($folder) use ($path) {
                return $path . $folder;
            }, $folders));
        }, []);
    }

    public function current()
    {
        return $this->folders[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->folders[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}