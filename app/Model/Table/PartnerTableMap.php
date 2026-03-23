<?php

declare(strict_types=1);

namespace App\Model\Table;

class PartnerTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_partners';
    public const string TABLE_PREFIX = 'partner_mapper';

    public const string COL_ID = 'id';
    public const string COL_NAME = 'name';
    public const string COL_STREET = 'street';
    public const string COL_STREET_NUMBER = 'street_number';
    public const string COL_PSC = 'psc';
    public const string COL_CITY = 'city';
    public const string COL_STATE = 'state';
    public const string COL_ICO = 'ico';
    public const string COL_IC_DPH = 'ic_dph';
    public const string COL_WEB = 'web';
    public const string COL_PHONE = 'phone';
    public const string COL_EMAIL = 'email';
    public const string COL_DATE_START = 'date_start';
    public const string COL_STATUS = 'status';
    public const string COL_ACTIVE = 'active';
    public const string COL_PERSON_NAME = 'person_name';
    public const string COL_PERSON_SURNAME = 'person_surname';
    public const string COL_NOTICE = 'notice';
}
