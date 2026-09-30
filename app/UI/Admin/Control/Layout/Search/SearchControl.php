<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Search;

use App\Model\Repository\SearchRepository;
use Nette\Application\UI\Control;
use Tracy\Debugger;

class SearchControl extends Control
{
	private const array MOBILE_TYPE_ORDER = [
		SearchRepository::TYPE_BABYSITTER,
		SearchRepository::TYPE_FAMILY,
		SearchRepository::TYPE_FAMILY_CONTACT,
		SearchRepository::TYPE_PARTNER,
		SearchRepository::TYPE_AGENCY,
	];

	private const int MOBILE_PREVIEW_LIMIT = 5;

	public function __construct(
		private readonly SearchRepository $searchRepository,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/SearchControl.latte');
		$template->render();
	}

	public function handleSearch(?int $type = null, string $term = ''): void
	{
		$type = $this->normalizeType($type ?? SearchRepository::TYPE_FAMILY);
		$term = trim($term);
		$rows = $this->searchRepository->search($type, $term);

		Debugger::log(sprintf(
			'Search request: type=%d, term="%s", results=%d',
			$type,
			$term,
			count($rows),
		), 'search');

		$template = $this->createTemplate();
		$template->setFile(__DIR__ . '/templates/SearchResults.latte');
		$template->type = $type;
		$template->rows = $rows;
		$template->heading = $this->getHeading($type);
		$template->tableTitle = $this->getTableTitle($type);

		ob_start();
		$template->render();
		$html = (string) ob_get_clean();

		$this->getPresenter()->sendJson(['html' => $html]);
	}

	/**
	 * Mobile search: without type searches all types at once (grouped sections), with type only that one.
	 */
	public function handleSearchMobile(string $term = '', ?int $type = null): void
	{
		$term = trim($term);
		$types = $type === null ? self::MOBILE_TYPE_ORDER : [$this->normalizeType($type)];

		$sections = [];
		$counts = [];
		foreach ($types as $sectionType) {
			$rows = $this->searchRepository->search($sectionType, $term);
			$counts[$sectionType] = count($rows);
			$sections[] = [
				'type' => $sectionType,
				'label' => $this->getMobileLabel($sectionType),
				'rows' => $rows,
				'count' => count($rows),
			];
		}

		Debugger::log(sprintf(
			'Mobile search request: type=%s, term="%s", results=%d',
			$type === null ? 'all' : (string) $type,
			$term,
			array_sum($counts),
		), 'search');

		$template = $this->createTemplate();
		$template->setFile(__DIR__ . '/templates/SearchResultsMobile.latte');
		$template->sections = $sections;
		$template->allMode = $type === null;
		$template->term = $term;
		$template->previewLimit = self::MOBILE_PREVIEW_LIMIT;

		ob_start();
		$template->render();
		$html = (string) ob_get_clean();

		$this->getPresenter()->sendJson(['html' => $html, 'counts' => $counts]);
	}

	private function getMobileLabel(int $type): string
	{
		return match ($type) {
			SearchRepository::TYPE_BABYSITTER => 'Opatrovateľky',
			SearchRepository::TYPE_FAMILY => 'Rodiny',
			SearchRepository::TYPE_FAMILY_CONTACT => 'Kontaktné osoby',
			SearchRepository::TYPE_PARTNER => 'Partneri',
			SearchRepository::TYPE_AGENCY => 'Agentúry',
			default => 'Výsledky',
		};
	}

	private function normalizeType(int $type): int
	{
		return in_array($type, [
			SearchRepository::TYPE_BABYSITTER,
			SearchRepository::TYPE_FAMILY,
			SearchRepository::TYPE_PARTNER,
			SearchRepository::TYPE_AGENCY,
			SearchRepository::TYPE_FAMILY_CONTACT,
		], true) ? $type : SearchRepository::TYPE_FAMILY;
	}

	private function getHeading(int $type): string
	{
		return match ($type) {
			SearchRepository::TYPE_BABYSITTER => 'Výsledky vyhľadávania - opatrovateľky',
			SearchRepository::TYPE_FAMILY => 'Výsledky vyhľadávania - rodiny',
			SearchRepository::TYPE_PARTNER => 'Výsledky vyhľadávania - partneri',
			SearchRepository::TYPE_AGENCY => 'Výsledky vyhľadávania - agentúry',
			SearchRepository::TYPE_FAMILY_CONTACT => 'Výsledky vyhľadávania - kontaktné osoby rodiny',
			default => 'Výsledky vyhľadávania',
		};
	}

	private function getTableTitle(int $type): string
	{
		return match ($type) {
			SearchRepository::TYPE_BABYSITTER => 'Zoznam opatrovateliek',
			SearchRepository::TYPE_PARTNER => 'Zoznam partnerov',
			SearchRepository::TYPE_AGENCY => 'Zoznam agentúr',
			default => 'Zoznam rodín',
		};
	}
}
