<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\FamilyProposalEntity;
use App\Model\Table\FamilyProposalTableMap;

class FamilyProposalFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return FamilyProposalEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return FamilyProposalTableMap::class;
    }
}
