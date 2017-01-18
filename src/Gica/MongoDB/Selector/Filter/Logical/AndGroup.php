<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector\Filter\Logical;

use Gica\MongoDB\Selector\Filter;

class AndGroup implements Filter
{
    /**
     * @var \Gica\MongoDB\Selector\Filter[]
     */
    private $filters;

    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }

    public function getFields():array
    {
        $filterExpressions = [];

        foreach ($this->filters as $filter) {
            $filterExpressions[] = $filter->getFields();
        }

        return [
            '$and' => $filterExpressions,
        ];
    }
}