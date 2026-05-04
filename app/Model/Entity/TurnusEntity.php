<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class TurnusEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $babysitterId,
        public ?int $familyId,
        public ?int $agencyId,
        public ?int $partnerId,
        public ?int $status,
        public ?string $invoiceNumber,
        public ?string $preinvoiceNumber,
        public ?int $invoiceStatus,
        public ?string $complaint,
        public ?int $complaintStatus,
        public ?DateTimeImmutable $dateCreated,
        public ?int $workingStatus,
        public ?int $userCreated,
        public ?int $userId,
        public ?float $bonus,
        public ?float $holiday,
        public ?float $commissionComplet,
        public ?float $commissionPartners,
        public ?int $paymentPeriodPartner,
        public ?float $commission4ms,
        public ?int $paymentPeriod,
        public ?float $remainingPayment,
        public ?string $travelExpenses,
        public ?string $sva,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public ?float $travelCostsArrival,
        public ?float $travelCostsDeparture,
        public ?float $fee,
        public ?float $feeAg,
        public ?float $feeBk,
        public ?string $notice,
        public ?int $active,
        public ?int $statusA1,
        public ?int $deleted,
        public ?int $workPositionId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
