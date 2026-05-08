<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Turnus\TurnusUpdate;

final readonly class TurnusUpdateForm
{
	public function __construct(
		public int $id,
		public int $status,
		public int $familyId,
		public int $babysitterId,
		public ?\DateTimeImmutable $dateFrom,
		public ?\DateTimeImmutable $dateTo,
		public int $userId,
		public int $agencyId,
		public int $partnerId,
		public int $workingStatus,
		public int $workPositionId,
		public string $preinvoiceNumber,
		public string $invoiceNumber,
		public int $invoiceStatus,
		public float $fee,
		public float $feeAg,
		public float $feeBk,
		public float $travelCostsArrival,
		public float $travelCostsDeparture,
		public string $travelExpenses,
		public float $bonus,
		public float $holiday,
		public string $sva,
		public float $commissionComplet,
		public float $commissionPartners,
		public int $paymentPeriodPartner,
		public float $commission4ms,
		public int $paymentPeriod,
		public ?float $remainingPayment,
		public string $notice,
		public string $complaint,
		public int $complaintStatus,
	) {
	}
}
