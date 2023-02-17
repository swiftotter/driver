<?php

declare(strict_types=1);

namespace Driver\System\Configuration;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_unique;
use function file_exists;

class FileCollector
{
    private const FILE_EXTENSION = '.yaml';
    private const ALLOWED_FOLDERS = [
        '.driver'
    ];
    private const ALLOWED_FILES = [
        'anonymize',
        'pipelines',
        'commands',
        'engines',
        'connections',
        'config',
        'reduce',
        'environments',
        'update_values'
    ];

    /**
     * @return string[]
     */
    public function get(): array
    {
        $folderCollection = (new FolderCollectionFactory())->create(self::ALLOWED_FOLDERS);
        $output = [];

        foreach ($folderCollection as $folder) {
            $files = array_filter(self::ALLOWED_FILES, function ($file) use ($folder) {
                return file_exists($folder . '/' . $file . self::FILE_EXTENSION);
            });

            $output = array_merge($output, array_map(function ($file) use ($folder) {
                return $folder . '/' . $file . self::FILE_EXTENSION;
            }, $files));
        }

        return array_unique(array_reverse($output));
    }

    /**
     * @return string[]
     */
    public function getIndividual(string $file): array
    {
        $folderCollection = (new FolderCollectionFactory())->create(self::ALLOWED_FOLDERS);
        $output = [];

        foreach ($folderCollection as $folder) {
            $path = $folder . '/' . $file . self::FILE_EXTENSION;

            if (file_exists($path)) {
                $output[] = $path;
            }
        }

        return $output;
    }
}
