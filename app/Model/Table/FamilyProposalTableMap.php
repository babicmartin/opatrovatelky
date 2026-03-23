<?php

declare(strict_types=1);

namespace App\Model\Table;

class FamilyProposalTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_family_proposal';
    public const string TABLE_PREFIX = 'family_proposal_mapper';

    public const string COL_ID = 'id';
    public const string COL_FAMILY_ID = 'family_id';
    public const string COL_BABYSITTER_ID = 'babysitter_id';
    public const string COL_DATE_STARTING_WORK = 'date_starting_work';
    public const string COL_DATE_PROPOSAL_SENDED = 'date_proposal_sended';
    public const string COL_STATUS = 'status';
    public const string COL_NOTICE = 'notice';
    public const string COL_USER_CREATED = 'user_created';
    public const string COL_DATE_CREATED = 'date_created';
    public const string COL_DELETED = 'deleted';
}
