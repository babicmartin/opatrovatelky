<?php

declare(strict_types=1);

namespace App\Model\Table;

class BabysitterDiseaseTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_babysitter_disease';
    public const string TABLE_PREFIX = 'babysitter_disease_mapper';

    public const string COL_ID = 'id';
    public const string COL_BABYSITTER_ID = 'babysitter_id';
    public const string COL_DISEASE_ID = 'disease_id';
}
