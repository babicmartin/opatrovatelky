<?php

declare(strict_types=1);

namespace App\Model\Entity;

use ReflectionClass;
use ReflectionProperty;

abstract class BaseEntity
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
        $data = [];

        foreach ($properties as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }
}
