<?php

declare(strict_types=1);

namespace App\Model\Table;

class TurnusTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_turnus';
    public const string TABLE_PREFIX = 'turnus_mapper';

    public const string COL_ID = 'id';
    public const string COL_BABYSITTER_ID = 'babysitter_id';
    public const string COL_FAMILY_ID = 'family_id';
    public const string COL_AGENCY_ID = 'agency_id';
    public const string COL_PARTNER_ID = 'partner_id';
    public const string COL_STATUS = 'status';
    public const string COL_INVOICE_NUMBER = 'invoice_number';
    public const string COL_PREINVOICE_NUMBER = 'preinvoice_number';
    public const string COL_INVOICE_STATUS = 'invoice_status';
    public const string COL_COMPLAINT = 'complaint';
    public const string COL_COMPLAINT_STATUS = 'complaint_status';
    public const string COL_DATE_CREATED = 'date_created';
    public const string COL_WORKING_STATUS = 'working_status';
    public const string COL_USER_CREATED = 'user_created';
    public const string COL_USER_ID = 'user_id';
    public const string COL_BONUS = 'bonus';
    public const string COL_HOLIDAY = 'holiday';
    public const string COL_COMMISSION_COMPLET = 'commission_complet';
    public const string COL_COMMISSION_PARTNERS = 'commission_partners';
    public const string COL_PAYMENT_PERIOD_PARTNER = 'payment_period_partner';
    public const string COL_COMMISSION_4MS = 'commission_4ms';
    public const string COL_PAYMENT_PERIOD = 'payment_period';
    public const string COL_REMAINING_PAYMENT = 'remaining_payment';
    public const string COL_TRAVEL_EXPENSES = 'travel_expenses';
    public const string COL_SVA = 'sva';
    public const string COL_DATE_FROM = 'date_from';
    public const string COL_DATE_TO = 'date_to';
    public const string COL_TRAVEL_COSTS_ARRIVAL = 'travel_costs_arrival';
    public const string COL_TRAVEL_COSTS_DEPARTURE = 'travel_costs_departure';
    public const string COL_FEE = 'fee';
    public const string COL_FEE_AG = 'fee_ag';
    public const string COL_FEE_BK = 'fee_bk';
    public const string COL_NOTICE = 'notice';
    public const string COL_ACTIVE = 'active';
    public const string COL_STATUS_A1 = 'status_a1';
    public const string COL_DELETED = 'deleted';
    public const string COL_WORK_POSITION_ID = 'work_position_id';
}
