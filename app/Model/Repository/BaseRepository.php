<?php

declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Factory\BaseFactory;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

abstract class BaseRepository
{
    public function __construct(
        protected Explorer $database,
    ) {
    }

    abstract protected function getTableName(): string;

    /**
     * Override in subclass to enable entity wrapping.
     */
    protected function getFactory(): ?BaseFactory
    {
        return null;
    }

    /**
     * @return Selection<ActiveRow>
     */
    protected function findAll(): Selection
    {
        return $this->database->table($this->getTableName());
    }

    /**
     * @param array<string, mixed> $data
     * @return ActiveRow|int|bool
     */
    protected function insert(array $data): ActiveRow|int|bool
    {
        $result = $this->findAll()->insert($data);
        if (is_array($result)) {
            throw new \RuntimeException('Insert returned multiple results, expected single.');
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function update(int $id, array $data): int
    {
        return $this->findAll()->where('id', $id)->update($data);
    }

    protected function getItem(int $id): ?ActiveRow
    {
        return $this->findAll()->get($id);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return Selection<ActiveRow>
     */
    protected function getItems(array $criteria = []): Selection
    {
        return $this->findAll()->where($criteria);
    }

    /**
     * @param Selection<ActiveRow> $selection
     * @return list<object>
     */
    protected function wrapToEntities(Selection $selection): array
    {
        $factory = $this->getFactory();
        if ($factory === null) {
            throw new \LogicException('Factory not set. Override getFactory() in ' . static::class);
        }

        return $factory->createEntitiesFromRows($selection);
    }
}
