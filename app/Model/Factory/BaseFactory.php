<?php

declare(strict_types=1);

namespace App\Model\Factory;

use Nette\Database\Table\ActiveRow;
use ReflectionClass;

abstract class BaseFactory
{
    /**
     * @return class-string
     */
    abstract protected function getEntityClass(): string;

    /**
     * @return class-string
     */
    abstract protected function getTableMapClass(): string;

    public function createFromActiveRow(ActiveRow $row): object
    {
        $tableMapClass = $this->getTableMapClass();
        $entityClass = $this->getEntityClass();

        $reflection = new ReflectionClass($tableMapClass);
        $constants = $reflection->getConstants();

        $args = [];
        foreach ($constants as $name => $columnName) {
            if (!str_starts_with($name, 'COL_')) {
                continue;
            }

            if (!is_string($columnName)) {
                continue;
            }

            // Convert snake_case column to camelCase property/argument name
            $propertyName = $this->snakeToCamel($columnName);

            // Assign value from row
            $args[$propertyName] = $row->$columnName;
        }

        return new $entityClass(...$args);
    }

    /**
     * @param iterable<ActiveRow> $rows
     * @return list<object>
     */
    public function createEntitiesFromRows(iterable $rows): array
    {
        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->createFromActiveRow($row);
        }
        return $entities;
    }

    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }
}
