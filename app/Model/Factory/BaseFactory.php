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

            // Convert snake_case column to camelCase property/argument name
            $propertyName = $this->snakeToCamel($columnName);

            // Assign value from row
            $args[$propertyName] = $row->$columnName;
        }

        return new $entityClass(...$args);
    }

    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }
}
