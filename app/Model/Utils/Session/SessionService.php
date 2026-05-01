<?php declare(strict_types = 1);

namespace App\Model\Utils\Session;

use Nette\Http\Session;

final readonly class SessionService
{
	public function __construct(
		private Session $session,
	)
	{
	}

	public function getSession(string $section, string $name): mixed
	{
		return $this->session->getSection($section)->$name;
	}

	public function createSession(string $section, string $name, mixed $value): void
	{
		$this->session->getSection($section)->$name = $value;
	}

	public function removeSection(string $section): void
	{
		$sectionInstance = $this->session->getSection($section);
		$sectionInstance->remove();
	}
}
