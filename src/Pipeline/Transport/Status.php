<?php

declare(strict_types=1);

namespace Driver\Pipeline\Transport;

class Status
{
    private string $node;
    private string $message;
    private bool $isError;

    public function __construct(string $node, string $message, bool $isError = false)
    {
        $this->node = $node;
        $this->message = $message;
        $this->isError = $isError;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function getNode(): string
    {
        return $this->node;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
