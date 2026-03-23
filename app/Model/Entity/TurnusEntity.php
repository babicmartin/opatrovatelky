<?php

declare(strict_types=1);

namespace App\Model\Entity;

class TurnusEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $babysitterId {
            get {
                return $this->babysitterId;
            }
            set {
                $this->babysitterId = $value;
            }
        },
        private ?string $familyId {
            get {
                return $this->familyId;
            }
            set {
                $this->familyId = $value;
            }
        },
        private ?string $agencyId {
            get {
                return $this->agencyId;
            }
            set {
                $this->agencyId = $value;
            }
        },
        private ?string $partnerId {
            get {
                return $this->partnerId;
            }
            set {
                $this->partnerId = $value;
            }
        },
        private ?string $status {
            get {
                return $this->status;
            }
            set {
                $this->status = $value;
            }
        },
        private ?string $invoiceNumber {
            get {
                return $this->invoiceNumber;
            }
            set {
                $this->invoiceNumber = $value;
            }
        },
        private ?string $preinvoiceNumber {
            get {
                return $this->preinvoiceNumber;
            }
            set {
                $this->preinvoiceNumber = $value;
            }
        },
        private ?string $invoiceStatus {
            get {
                return $this->invoiceStatus;
            }
            set {
                $this->invoiceStatus = $value;
            }
        },
        private ?string $complaint {
            get {
                return $this->complaint;
            }
            set {
                $this->complaint = $value;
            }
        },
        private ?string $complaintStatus {
            get {
                return $this->complaintStatus;
            }
            set {
                $this->complaintStatus = $value;
            }
        },
        private ?string $dateCreated {
            get {
                return $this->dateCreated;
            }
            set {
                $this->dateCreated = $value;
            }
        },
        private ?string $workingStatus {
            get {
                return $this->workingStatus;
            }
            set {
                $this->workingStatus = $value;
            }
        },
        private ?string $userCreated {
            get {
                return $this->userCreated;
            }
            set {
                $this->userCreated = $value;
            }
        },
        private ?string $userId {
            get {
                return $this->userId;
            }
            set {
                $this->userId = $value;
            }
        },
        private ?string $bonus {
            get {
                return $this->bonus;
            }
            set {
                $this->bonus = $value;
            }
        },
        private ?string $holiday {
            get {
                return $this->holiday;
            }
            set {
                $this->holiday = $value;
            }
        },
        private ?string $commissionComplet {
            get {
                return $this->commissionComplet;
            }
            set {
                $this->commissionComplet = $value;
            }
        },
        private ?string $commissionPartners {
            get {
                return $this->commissionPartners;
            }
            set {
                $this->commissionPartners = $value;
            }
        },
        private ?string $paymentPeriodPartner {
            get {
                return $this->paymentPeriodPartner;
            }
            set {
                $this->paymentPeriodPartner = $value;
            }
        },
        private ?string $commission4ms {
            get {
                return $this->commission4ms;
            }
            set {
                $this->commission4ms = $value;
            }
        },
        private ?string $paymentPeriod {
            get {
                return $this->paymentPeriod;
            }
            set {
                $this->paymentPeriod = $value;
            }
        },
        private ?string $remainingPayment {
            get {
                return $this->remainingPayment;
            }
            set {
                $this->remainingPayment = $value;
            }
        },
        private ?string $travelExpenses {
            get {
                return $this->travelExpenses;
            }
            set {
                $this->travelExpenses = $value;
            }
        },
        private ?string $sva {
            get {
                return $this->sva;
            }
            set {
                $this->sva = $value;
            }
        },
        private ?string $dateFrom {
            get {
                return $this->dateFrom;
            }
            set {
                $this->dateFrom = $value;
            }
        },
        private ?string $dateTo {
            get {
                return $this->dateTo;
            }
            set {
                $this->dateTo = $value;
            }
        },
        private ?string $travelCostsArrival {
            get {
                return $this->travelCostsArrival;
            }
            set {
                $this->travelCostsArrival = $value;
            }
        },
        private ?string $travelCostsDeparture {
            get {
                return $this->travelCostsDeparture;
            }
            set {
                $this->travelCostsDeparture = $value;
            }
        },
        private ?string $fee {
            get {
                return $this->fee;
            }
            set {
                $this->fee = $value;
            }
        },
        private ?string $feeAg {
            get {
                return $this->feeAg;
            }
            set {
                $this->feeAg = $value;
            }
        },
        private ?string $notice {
            get {
                return $this->notice;
            }
            set {
                $this->notice = $value;
            }
        },
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
            }
        },
        private ?string $statusA1 {
            get {
                return $this->statusA1;
            }
            set {
                $this->statusA1 = $value;
            }
        },
        private ?string $deleted {
            get {
                return $this->deleted;
            }
            set {
                $this->deleted = $value;
            }
        },
        private ?string $workPositionId {
            get {
                return $this->workPositionId;
            }
            set {
                $this->workPositionId = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
