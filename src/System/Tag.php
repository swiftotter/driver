<?php

declare(strict_types=1);

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
