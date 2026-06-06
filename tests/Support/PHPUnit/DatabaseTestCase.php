<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use Nette\Database\Explorer;
use Tests\Support\Database\TestDatabase;

abstract class DatabaseTestCase extends ContainerTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		TestDatabase::reset();
	}

	protected function getDatabase(): Explorer
	{
		return $this->getContainer()->getByType(Explorer::class);
	}
}
