<?php

declare(strict_types=1);

namespace App\Model\Table;

class FamilyTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_families';
    public const string TABLE_PREFIX = 'family_mapper';

    public const string COL_ID = 'id';
    public const string COL_CLIENT_NUMBER = 'client_number';
    public const string COL_NAME = 'name';
    public const string COL_SURNAME = 'surname';
    public const string COL_STREET = 'street';
    public const string COL_STREET_NUMBER = 'street_number';
    public const string COL_PSC = 'psc';
    public const string COL_CITY = 'city';
    public const string COL_STATE = 'state';
    public const string COL_PHONE = 'phone';
    public const string COL_PERSON_EMAIL = 'person_email';
    public const string COL_DATE_START = 'date_start';
    public const string COL_DATE_TO = 'date_to';
    public const string COL_STATUS = 'status';
    public const string COL_ACTIVE = 'active';
    public const string COL_PERSON_NAME = 'person_name';
    public const string COL_PERSON_SURNAME = 'person_surname';
    public const string COL_PERSON_PHONE = 'person_phone';
    public const string COL_NOTICE = 'notice';
    public const string COL_BILLING = 'billing';
    public const string COL_PARTNER_ID = 'partner_id';
    public const string COL_USER_ID = 'user_id';
    public const string COL_ACQUIRED_BY_USER_ID = 'acquired_by_user_id';
    public const string COL_ORDER_STATUS = 'order_status';
    public const string COL_CONTRACT_STATUS = 'contract_status';
    public const string COL_PATIENT_PHONE = 'patient_phone';
    public const string COL_DELETED = 'deleted';
    public const string COL_TYPE = 'type';
    public const string COL_COMPANY_NAME = 'company_name';
    public const string COL_EMPLOYER = 'employer';
    public const string COL_ACCOMMODATION_ADDRESS = 'accommodation_address';
    public const string COL_DE_PROJECT_NUMBER = 'de_project_number';
    public const string COL_PROJECT_DESCRIPTION = 'project_description';
    public const string COL_PROJECT_POSITIONS = 'project_positions';
    public const string COL_PROJECT_AVAILABLE_POSITIONS = 'project_available_positions';
    public const string COL_WORK_STATUS_STAFF = 'work_status_staff';
}
