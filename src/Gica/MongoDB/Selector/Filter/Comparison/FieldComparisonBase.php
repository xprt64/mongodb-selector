<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector\Filter\Comparison;


abstract class FieldComparisonBase implements \Gica\MongoDB\Selector\Filter
{
    private $fieldName;
    private $value;
    private $operator;

    public function __construct(string $fieldName, $operator, $value)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function getFields():array
    {
        return [
            $this->fieldName => [
                $this->operator => $this->value,
            ],
        ];
    }
}