<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector\Filter\Comparison;


use Gica\Selector\Filter;

class EqualDirect implements Filter
{
    public function __construct(private string $fieldName, private $value)
    {
    }

    #[\Override]
    public function applyFilter(array $fields):array
    {
        $fields[$this->fieldName] = $this->value;

        return $fields;
    }
}