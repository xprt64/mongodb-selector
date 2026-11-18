<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector;


class CountableIterator implements \Countable, \IteratorAggregate
{
    public function __construct(private Selector $selector)
    {
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function count(): int
    {
        return $this->selector->count();
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->selector->find();
    }
}