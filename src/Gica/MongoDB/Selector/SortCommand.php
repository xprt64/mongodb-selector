<?php
declare(strict_types=1);

namespace Gica\MongoDB\Selector;

interface SortCommand
{
    public function getField(): string;
    public function isAscending(): bool;
}