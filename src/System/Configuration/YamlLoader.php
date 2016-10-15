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
 * @copyright SwiftOtter Studios, 10/15/16
 * @package default
 **/

namespace Driver\System\Configuration;

class YamlLoader
{
    protected $fileExtension = '.yaml';

    protected $allowedFolders = [
        'config',
        'config.d'
    ];

    protected $allowedFiles = [
        'chain',
        'commands',
        'engines',
        'workshop',
        'connections',
        'config'
    ];

    public function get()
    {
        return $this->getFiltered();
    }

    public function getIndividual($file)
    {
        return $this->getFiltered($file);
    }

    protected function getFiltered($file = false)
    {
        $directories = new \RecursiveDirectoryIterator(realpath(__DIR__.'/../../../'));

        foreach (new \RecursiveIteratorIterator($directories) as $path) {
            if ($this->isAllowedFile($path, $file)) {
                yield $path;
            }
        }
    }

    public function load($file)
    {
        if (!$file || !file_exists($file)) {
            throw new \Exception("Filename: {$file} doesn't exist.");
        }
        return file_get_contents($file);
    }

    protected function isAllowedFile($path, $additionalFiles = [])
    {
        if (!is_array($additionalFiles) && !$additionalFiles) {
            $additionalFiles = [];
        } else if (!is_array($additionalFiles) && $additionalFiles) {
            $additionalFiles = [$additionalFiles];
        }

        if (count($additionalFiles)) {
            $fileSearch = $additionalFiles;
        } else {
            $fileSearch = $this->allowedFiles;
        }

        $usedAllowedFolders = array_filter($this->allowedFolders, function($allowedFolder) use ($path) {
            return strpos($path, '/'.$allowedFolder.'/') !== false;
        });

        $usedAllowedFiles = array_filter($fileSearch, function($allowedFile) use ($path) {
            $filename = explode('.', $allowedFile)[0] . $this->fileExtension;
            return substr($path, 0-strlen($filename)) === $filename;
        });

        return count($usedAllowedFolders) && count($usedAllowedFiles);
    }
}