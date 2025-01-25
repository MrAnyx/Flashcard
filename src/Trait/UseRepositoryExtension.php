<?php

declare(strict_types=1);

namespace App\Trait;

use App\Enum\OperatorType;
use App\Model\Filter;
use App\Model\Page;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Constraint\Operator;

trait UseRepositoryExtension
{
    public function addFilter(QueryBuilder &$query, string $alias, Filter $filter): void
    {
        // check if operator is like then add to lower case
        $parameter = $filter->getDoctrineParameter();
        $field = "{$alias}.{$filter->filter}";
        $operator = $filter->operator->getDoctrineNotation();

        if ($filter->operator === OperatorType::LIKE) {
            $parameter = mb_strtolower($parameter);
            $field = "LOWER({$field})";
        }

        $query
            ->andWhere("{$field} {$operator} :query")
            ->setParameter('query', $parameter);
    }

    public function addSort(QueryBuilder &$query, string $alias, Page $page): void
    {
        $field = "{$alias}.{$page->sort}";
        $order = $page->order->value;

        $query
            ->addOrderBy("CASE WHEN {$field} IS NULL THEN 1 ELSE 0 END", 'ASC') // To put null values last
            ->addOrderBy($field, $order);
    }
}
