<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector\Filter\Comparison;


class EqualDirect implements \Gica\MongoDB\Selector\Filter
{
    private $fieldName;
    private $value;

    public function __construct(string $fieldName, $value)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
    }

    public function getFields():array
    {
        return [
            $this->fieldName => $this->value,

        ];
    }
}