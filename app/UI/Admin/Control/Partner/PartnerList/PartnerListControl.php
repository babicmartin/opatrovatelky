<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Partner\PartnerList;

use App\Model\Repository\PartnerRepository;
use Nette\Application\UI\Control;

class PartnerListControl extends Control
{
	private ?int $countryId = null;

	private ?int $statusId = null;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly PartnerRepository $partnerRepository,
	) {
	}

	public function setFilters(?int $countryId, ?int $statusId): static
	{
		$this->countryId = $countryId;
		$this->statusId = $statusId;

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/PartnerListControl.latte');
		$template->rows = $this->getRows();
		$template->render();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRows(): array
	{
		if ($this->rows === null) {
			$this->rows = $this->partnerRepository->findPartnerRows($this->countryId, $this->statusId);
		}

		return $this->rows;
	}
}
