<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Filter\AlphabetFilter;

use Nette\Application\UI\Control;

class AlphabetFilterControl extends Control
{
	private ?string $selectedLetter = null;

	/** @var array<string, mixed> */
	private array $routeParams = [];

	/**
	 * @param array<string, mixed> $routeParams
	 */
	public function setFilterState(?string $selectedLetter, array $routeParams): static
	{
		$this->selectedLetter = $selectedLetter;
		unset($routeParams['page']);
		$this->routeParams = array_filter(
			$routeParams,
			static fn (mixed $value): bool => $value !== null && $value !== '',
		);

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/AlphabetFilterControl.latte');
		$template->letters = range('A', 'Z');
		$template->selectedLetter = $this->selectedLetter;
		$template->routeParams = $this->routeParams;
		$template->render();
	}
}
