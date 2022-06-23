<?php

declare(strict_types=1);

namespace Driver\System\Configuration;

class FolderCollection implements \Iterator
{
    /** @var string[] */
    private array $folders;

    private int $position = 0;

    /**
     * @param string[] $folders
     */
    public function __construct(array $folders)
    {
        $this->folders = $folders;
    }

    public function current(): string
    {
        return $this->folders[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->folders[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
