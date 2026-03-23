<?php

declare(strict_types=1);

namespace App\Model\Table;

class MissingRegistryTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_missing_registry';
    public const string TABLE_PREFIX = 'missing_registry_mapper';

    public const string COL_ID = 'id';
    public const string COL_USER_ID = 'user_id';
    public const string COL_DATE_FROM = 'date_from';
    public const string COL_DATE_TO = 'date_to';
    public const string COL_TYPE_PN = 'type_pn';
    public const string COL_TYPE_OCR = 'type_ocr';
    public const string COL_TYPE_LEKAR = 'type_lekar';
    public const string COL_TYPE_SVIATOK = 'type_sviatok';
    public const string COL_TYPE_ZASTUP = 'type_zastup';
    public const string COL_TYPE_SLUZBA = 'type_sluzba';
    public const string COL_TYPE_DOVOLENKA = 'type_dovolenka';
    public const string COL_NOTICE = 'notice';
    public const string COL_ACTIVE = 'active';
    public const string COL_DELETED = 'deleted';
}
