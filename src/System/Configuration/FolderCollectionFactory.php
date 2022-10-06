<?php

declare(strict_types=1);

namespace Driver\System\Configuration;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function dirname;
use function explode;
use function file_exists;
use function glob;
use function realpath;
use function rtrim;
use function strpos;

class FolderCollectionFactory
{
    private const VENDOR_DIRECTORY = 'vendor';

    /**
     * @param string[] $allowedFolders
     */
    public function create(array $allowedFolders): FolderCollection
    {
        return new FolderCollection($this->getFolders($this->getSearchPaths(), $allowedFolders));
    }

    /**
     * @return string[]
     */
    private function getSearchPaths(): array
    {
        $directory = realpath($_SERVER['SCRIPT_FILENAME']);
        if (strpos($directory, self::VENDOR_DIRECTORY) !== false) {
            list($rootDir) = explode(self::VENDOR_DIRECTORY, $directory);
            return array_merge([$rootDir], $this->getVendorDirectories($rootDir));
        }
        return [dirname($directory, 2)];
    }

    /**
     * @return string[]
     */
    private function getVendorDirectories(string $path): array
    {
        return glob($path . self::VENDOR_DIRECTORY . "/*/*/", GLOB_ONLYDIR);
    }

    /**
     * @param string[] $paths
     * @param string[] $allowedFolders
     * @return string[]
     */
    private function getFolders(array $paths, array $allowedFolders): array
    {
        return array_reduce($paths, function ($acc, $path) use ($allowedFolders) {
            $path = rtrim($path, '/') . '/';

            $folders = array_filter($allowedFolders, function ($folder) use ($path) {
                return file_exists($path . $folder);
            });

            return array_merge($acc, array_map(function ($folder) use ($path) {
                return $path . $folder;
            }, $folders));
        }, []);
    }
}
