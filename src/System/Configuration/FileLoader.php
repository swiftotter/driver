<?php

declare(strict_types=1);

namespace Driver\System\Configuration;

use Exception;

use function file_exists;

class FileLoader
{
    /**
     * Returns the contents of a file.
     * @throws Exception
     */
    public function load(string $file): string
    {
        if (!$file || !file_exists($file)) {
            throw new Exception("{$file} doesn't exist.");
        }
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("Unable to load {$file}");
        }
        return $content;
    }
}
