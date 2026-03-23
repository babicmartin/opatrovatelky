<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusProposalEntity;
use App\Model\Table\StatusProposalTableMap;

class StatusProposalFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusProposalEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusProposalTableMap::class;
    }
}
