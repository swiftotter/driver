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
        'pipelines',
        'commands',
        'engines',
        'connections',
        'config',
        'environments'
    ];

    public function get()
    {
        return $this->getAllFiltered();
    }

    public function getIndividual($file)
    {
        return $this->getFiltered($file);
    }

    protected function getFiltered($file)
    {
        $searchPath = new SearchPath(__DIR__, $this->allowedFolders);
        $output = [];

        foreach ($searchPath as $folder) {
            $path = $folder . '/' . $file . $this->fileExtension;

            if (file_exists($path)) {
                $output[] = $path;
            }
        }

        return $output;
    }

    protected function getAllFiltered()
    {
        $searchPath = new SearchPath(__DIR__, $this->allowedFolders);
        $output = [];

        foreach ($searchPath as $folder) {
            $files = array_filter($this->allowedFiles, function($file) use ($folder) {
                return file_exists($folder . '/' . $file . $this->fileExtension);
            });

            $output = array_merge($output, array_map(function($file) use ($folder) {
                return $folder . '/' . $file . $this->fileExtension;
            }, $files));
        }

        return array_reverse($output);
    }

    /**
     * Returns the contents of a yaml file.
     *
     * @param $file
     * @return string
     * @throws \Exception
     */
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