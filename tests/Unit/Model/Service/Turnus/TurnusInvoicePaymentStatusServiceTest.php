<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\Turnus;

use App\Model\Service\Turnus\TurnusInvoicePaymentStatusService;
use Tests\Support\PHPUnit\TestCase;

final class TurnusInvoicePaymentStatusServiceTest extends TestCase
{
	public function testIsInvoiceUnpaid(): void
	{
		$service = new TurnusInvoicePaymentStatusService();

		self::assertFalse($service->isInvoiceUnpaid(3));
		self::assertFalse($service->isInvoiceUnpaid(5));

		foreach ([0, 1, 2, 4, 6] as $invoiceStatusId) {
			self::assertTrue($service->isInvoiceUnpaid($invoiceStatusId));
		}
	}
}
