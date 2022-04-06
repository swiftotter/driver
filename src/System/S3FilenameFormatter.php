<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 4/5/22
 * @website https://swiftotter.com
 **/

namespace Driver\System;

use Driver\Pipeline\Environment\EnvironmentInterface;

class S3FilenameFormatter
{
    private Tag $tag;

    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    public function execute(EnvironmentInterface $environment)
    {
        $environment = '-' . $environment->getName();
        if ($this->tag->getTag()) {
            $environment .= '-' . $this->tag->getTag();
        }

        $replace = str_replace('{{environment}}', $environment, $this->getFileKey());
        $replace = str_replace('{{date}}', date('YmdHis'), $replace);

        return $replace;
    }
}
