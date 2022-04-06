<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 4/4/22
 * @website https://swiftotter.com
 **/

namespace Driver\System;

class Tag
{
    private ?string $tag = null;

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }
}
