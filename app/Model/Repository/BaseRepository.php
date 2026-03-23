<?php

declare(strict_types=1);

namespace App\Model\Repository;

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

    public function findAll(): Selection
    {
        return $this->database->table($this->getTableName());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): ActiveRow|int|bool
    {
        return $this->findAll()->insert($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int
    {
        return $this->findAll()->where('id', $id)->update($data);
    }

    public function getItem(int $id): ?ActiveRow
    {
        return $this->findAll()->get($id);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function getItems(array $criteria = []): Selection
    {
        return $this->findAll()->where($criteria);
    }
}
