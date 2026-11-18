<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector\Filter\Comparison;


use Gica\Selector\Filter;

abstract class FieldComparisonBase implements Filter
{
    public function __construct(private string $fieldName, private $operator, private $value)
    {
    }

    public function applyFilter(array $fields): array
    {
        $fields[$this->fieldName] = [
            $this->operator => $this->value,
        ];

        return $fields;
    }
}