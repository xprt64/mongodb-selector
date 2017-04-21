<?php


namespace Gica\MongoDB\Selector\Filter\Logical;

use Gica\MongoDB\Selector\Filter;

abstract class CompositeFilter
{
    /**
     * @var Filter[]
     */
    private $filters;

    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }

    public function withAddedFilter(Filter $filter): self
    {
        $other = clone $this;
        $other->filters[] = $filter;
        return $other;
    }

    public function getFields(): array
    {
        $filterExpressions = [];

        foreach ($this->filters as $filter) {
            $filterExpressions[] = $filter->getFields();
        }

        return [
            $this->getToken() => $filterExpressions,
        ];
    }

    abstract protected function getToken(): string;

    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }
}