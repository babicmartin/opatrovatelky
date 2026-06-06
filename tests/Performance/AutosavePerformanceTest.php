<?php declare(strict_types=1);

namespace Tests\Performance;

use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\OpatrovatelkaTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\FakePostRequest;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PerformanceAssertions;

/**
 * Guards the autosave single-field update + audit write latency.
 */
final class AutosavePerformanceTest extends DatabaseTestCase
{
	use PerformanceAssertions;

	public function testAutosaveFieldUpdateStaysFast(): void
	{
		$babysitterId = TestDatabase::createBabysitter();
		$service = $this->getContainer()->getByType(AutosaveFieldUpdateService::class);
		$counter = 0;

		$this->assertFasterThan('autosave_update', 120.0, static function () use ($service, $babysitterId, &$counter): void {
			$counter++;
			$service->tryHandleRequest(new FakePostRequest([
				'id' => (string) $babysitterId,
				'__autosave_context' => 'babysitter.address',
				'__autosave_field' => 'name',
				'__autosave_value' => 'Name' . $counter,
			]));
		});
	}
}
