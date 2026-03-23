<?php

declare(strict_types=1);

namespace App\Model\Table;

class StatusDocumentTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_status_documents';
    public const string TABLE_PREFIX = 'status_document_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
    public const string COL_COLOR = 'color';
}
