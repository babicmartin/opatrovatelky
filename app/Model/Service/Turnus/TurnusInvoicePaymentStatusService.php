<?php declare(strict_types=1);

namespace App\Model\Service\Turnus;

final class TurnusInvoicePaymentStatusService
{
	private const array PAID_INVOICE_STATUSES = [3, 5];

	public function isInvoiceUnpaid(int $invoiceStatus): bool
	{
		return !in_array($invoiceStatus, self::PAID_INVOICE_STATUSES, true);
	}
}
