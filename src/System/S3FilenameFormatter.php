<?php

declare(strict_types=1);

namespace Driver\System;

use Driver\Pipeline\Environment\EnvironmentInterface;

class S3FilenameFormatter
{
    private Tag $tag;

    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    public function execute(EnvironmentInterface $environment, ?string $fileKey): string
    {
        $environment = '-' . $environment->getName();
        if ($this->tag->getTag()) {
            $environment .= '-' . $this->tag->getTag();
        }

        $replace = str_replace('{{environment}}', $environment, $fileKey);
        return str_replace('{{date}}', date('YmdHis'), $replace);
    }
}
