<?php
/******************************************************************************
 * Copyright (c) 2017 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Gica\MongoDB\Selector;


use Gica\Iterator\IteratorTransformer\IteratorMapper;

class Selector implements \IteratorAggregate, \Countable
{
    /** @var \Gica\MongoDB\Selector\Filter[] */
    private $filters;
    private $skip;
    private $limit;
    private $sort;
    /**
     * @var \MongoDB\Collection
     */
    private $collection;

    static $sequence = 0;

    public $debug = false;

    private $iteratorMapper = null;

    public function __construct(
        \MongoDB\Collection $collection
    )
    {
        $this->filters = [];
        $this->collection = $collection;
    }

    public function addFilter(Filter $filter, $filterId = null)
    {
        if (null === $filterId) {
            $filterId = 'unnamed_filter_' . self::$sequence++;
        }

        $this->filters[$filterId] = $filter;
    }

    public function removeFilter($filter)
    {
        unset($this->filters[$filter]);
    }

    public function removeFilterById($filterId)
    {
        unset($this->filters[$filterId]);
    }

    public function withRemovedFilterById($filter):self
    {
        $other  = clone $this;

        $other->removeFilterById($filter);

        return $other;
    }

    public function skip($skip)
    {
        $this->skip = $skip;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
    }

    public function sort($field, $ascending)
    {
        $this->sort[$field] = ($ascending ? 1 : -1);
    }

    public function clearSort()
    {
        $this->sort = [];
    }

    public function constructQuery():array
    {
        $query = [];

        foreach ($this->filters as $filter) {
            $query = array_merge($query, $filter->getFields());
        }

        return $query;
    }

    public function find($projection = null)
    {
        $query = $this->constructQuery();

        $options = $this->getFindOptions();

        if ($projection) {
            $options['projection'] = $projection;
        }

        if ($this->debug) {
            echo $this->collection->getCollectionName() . "\n";
            var_dump($query);
            var_dump($options);
            die();
        }


        return $this->collection->find($query, $options);
    }

    public function fetchAsDto(callable $deserializer):array
    {
        $cursor = $this->find();

        $toDto = new IteratorMapper($deserializer);

        return iterator_to_array($toDto($cursor));
    }

    public function count()
    {
        $query = $this->constructQuery();

        return $this->collection->count($query);
    }

    private function getFindOptions()
    {
        $options = [];

        if ($this->skip > 0) {
            $options['skip'] = $this->skip;
        }

        if ($this->limit > 0) {
            $options['limit'] = $this->limit;
        }

        if ($this->sort) {
            $options['sort'] = $this->sort;
        }

        return $options;
    }

    /**
     * @param callable $iteratorMapper
     */
    public function setIteratorMapper($iteratorMapper)
    {
        $this->iteratorMapper = $iteratorMapper;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        $cursor = $this->find();
        if (is_callable($this->iteratorMapper)) {
            return ($this->iteratorMapper)($cursor);
        }
        return $cursor;
    }

    /**
     * @param $fieldName
     * @return CountByFieldResult[]
     */
    public function countByField($fieldName)
    {
        $query = $this->constructQuery();

        $mongoStack = [];

        if ($query) {
            $mongoStack[] = [
                '$match' => $query,
            ];
        }

        $mongoStack[] = [
            '$group' => [
                '_id'   => '$' . $fieldName,
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $toDto = new IteratorMapper(function ($document) {
            return new CountByFieldResult($document['_id'], $document['count']);
        });

        $cursor = $this->collection->aggregate($mongoStack);

        return iterator_to_array($toDto($cursor));
    }


    /**
     * @param $fieldName
     * @return float
     */
    public function sumField($fieldName)
    {
        $query = $this->constructQuery();

        $mongoStack = [];

        if ($query) {
            $mongoStack[] = [
                '$match' => $query,
            ];
        }

        $mongoStack[] = [
            '$group' => [
                '_id'   => null,
                'result' => [
                    '$sum' =>  '$' . $fieldName,
                ],
            ],
        ];

        $toDto = new IteratorMapper(function ($document) {
            return $document['result'];
        });

        $cursor = $this->collection->aggregate($mongoStack);

        return iterator_to_array($toDto($cursor))[0];
    }

    public function extractDistinctNestedField($fieldPath, array $distinctFields, $limit = null, $skip = null, $sortBy = null, $sortAscending = true)
    {
        $query = $this->constructQuery();

        $mongoStack = [
            [
                '$match' => $query,
            ],
        ];

        $fields = explode('.', $fieldPath);

        foreach ($fields as $field) {
            $mongoStack[] = [
                '$unwind' => '$' . $field,
            ];
        }

        $group = [
            'count' => [
                '$sum' => 1,
            ],
        ];

        foreach ($distinctFields as $field) {
            $group['_id'][$this->escapeFieldName($field)] = '$' . $fieldPath . '.' . $field;
        }

        $mongoStack[] = [
            '$group' => $group,
        ];

        if ($sortBy) {
            $sortByEscaped = $this->escapeFieldName($sortBy);
            $mongoStack[] = [
                '$sort' => ['_id.' . $sortByEscaped => $sortAscending ? 1 : -1],
            ];
        }

        if ($skip > 0) {
            $mongoStack[] = [
                '$skip' => $skip,
            ];
        }

        if ($limit > 0) {
            $mongoStack[] = [
                '$limit' => $limit,
            ];
        }

//        var_dump($mongoStack);
//        die();

        $unEscaper = new IteratorMapper(function ($document) {
            $result = [];
            foreach ($document as $k => $v) {
                $result[$this->unEscapeFieldName($k)] = $v;
            }
            return $result;
        });

        $toDto = new IteratorMapper(function ($document) {
            $result = $document['_id'];
            $result['count'] = $document['count'];
            return $result;
        });

        $cursor = $this->collection->aggregate($mongoStack);

        return $unEscaper($toDto($cursor));
    }

    public function getDistinctNestedFieldCount($fieldPath, array $distinctFields)
    {
        $query = $this->constructQuery();

        $mongoStack = [
            [
                '$match' => $query,
            ],
        ];

        $fields = explode('.', $fieldPath);

        foreach ($fields as $field) {
            $mongoStack[] = [
                '$unwind' => '$' . $field,
            ];
        }

        $group = [
        ];

        foreach ($distinctFields as $field) {
            $group['_id'][$field] = '$' . $fieldPath . '.' . $field;
        }

        $mongoStack[] = [
            '$group' => $group,
        ];

        $mongoStack[] = [
            '$group' => [
                '_id'   => null,
                'count' => ['$sum' => 1],
            ],
        ];

        $cursor = $this->collection->aggregate($mongoStack);

        $result = iterator_to_array($cursor);

        return reset($result)['count'];
    }

    private function escapeFieldName($field)
    {
        return str_replace('.', '____', $field);
    }

    private function unEscapeFieldName($field)
    {
        return str_replace('____', '.', $field);
    }
}