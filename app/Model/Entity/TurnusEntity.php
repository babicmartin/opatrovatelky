<?php

declare(strict_types=1);

namespace App\Model\Entity;

class TurnusEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $babysitterId,
        public ?string $familyId,
        public ?string $agencyId,
        public ?string $partnerId,
        public ?string $status,
        public ?string $invoiceNumber,
        public ?string $preinvoiceNumber,
        public ?string $invoiceStatus,
        public ?string $complaint,
        public ?string $complaintStatus,
        public ?string $dateCreated,
        public ?string $workingStatus,
        public ?string $userCreated,
        public ?string $userId,
        public ?string $bonus,
        public ?string $holiday,
        public ?string $commissionComplet,
        public ?string $commissionPartners,
        public ?string $paymentPeriodPartner,
        public ?string $commission4ms,
        public ?string $paymentPeriod,
        public ?string $remainingPayment,
        public ?string $travelExpenses,
        public ?string $sva,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $travelCostsArrival,
        public ?string $travelCostsDeparture,
        public ?string $fee,
        public ?string $feeAg,
        public ?string $notice,
        public ?string $active,
        public ?string $statusA1,
        public ?string $deleted,
        public ?string $workPositionId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
