<?php

namespace IOL\SSO\v1\Collections;


use JetBrains\PhpStorm\Pure;
use ReturnTypeWillChange;

class Collection implements \Iterator, \Countable
{
    protected int $position = 0;

    protected array $contents = [];

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->contents[$this->position];
    }

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange]
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @inheritDoc
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return isset($this->contents[$this->position]);
    }

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange]
    public function rewind(): void
    {
        $this->position = 0;
    }


    public function count(): int
    {
        return count($this->contents);
    }

    #[Pure] public function serialize(): array
    {
        $return = [];
        foreach ($this->contents as $content) {
            $return[] = $content->serialize();
        }
        return $return;
    }
}