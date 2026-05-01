<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Search;

use App\Model\Repository\SearchRepository;
use Nette\Application\UI\Control;
use Tracy\Debugger;

class SearchControl extends Control
{
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
