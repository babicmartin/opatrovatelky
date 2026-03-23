<?php

declare(strict_types=1);

namespace App\Model\Table;

class StatusDocumentA1TableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_status_documents_a1';
    public const string TABLE_PREFIX = 'status_document_a1_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
    public const string COL_COLOR = 'color';
    public const string COL_ICON = 'icon';
}
