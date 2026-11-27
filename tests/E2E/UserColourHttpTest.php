<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Table\UserTableMap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * The colour of a user is printed into the pages of colleagues (documents, lists, the calendar).
 * Both ways of saving the own profile must store a colour and nothing else.
 */
final class UserColourHttpTest extends DatabaseTestCase
{
	private const string Original = '#8A2062';

	private TestWebClient $web;
	private int $userId;


	protected function setUp(): void
	{
		parent::setUp();
		$this->userId = TestDatabase::createUser([UserTableMap::COL_COLOR => self::Original]);
		$this->web = new TestWebClient();
		self::assertSame(303, $this->web->login('test.user@example.test', 'secret123!')->getStatusCode());
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		parent::tearDown();
	}


	/** @return iterable<string, array{string, bool}> */
	public static function colours(): iterable
	{
		yield 'hex' => ['#00aa55', true];
		yield 'name' => ['blue', true];
		yield 'second declaration' => ['red;zoom:9', false];
		yield 'function' => ['url(//x)', false];
		yield 'markup' => ['"><b>', false];
	}


	#[DataProvider('colours')]
	public function testAutosaveStoresOnlyAColour(string $colour, bool $accepted): void
	{
		$response = $this->web->post($this->profilePage(), [
			'_do' => 'autosavePartial',
			'id' => (string) $this->userId,
			'__autosave_context' => 'user.profile',
			'__autosave_field' => 'color',
			'__autosave_value' => $colour,
		], ['X-Requested-With' => 'XMLHttpRequest']);

		self::assertSame($accepted ? 200 : 400, $response->getStatusCode());
		self::assertSame($accepted ? $colour : self::Original, $this->storedColour());
	}


	#[DataProvider('colours')]
	public function testWholeProfileFormStoresOnlyAColour(string $colour, bool $accepted): void
	{
		$response = $this->web->submitForm($this->profilePage(), 'frm-userProfileUpdateForm', [
			'name' => 'Test',
			'secondName' => 'User',
			'acronym' => 'TU',
			'email' => 'test.user@example.test',
			'color' => $colour,
		]);

		// A refused value shows the form again with its error; an accepted one redirects.
		self::assertSame($accepted ? 303 : 200, $response->getStatusCode());
		self::assertSame($accepted ? $colour : self::Original, $this->storedColour());
		if (!$accepted) {
			self::assertStringContainsString('Farba musí byť v tvare', (string) $response->getBody());
		}
	}


	private function profilePage(): string
	{
		return '/user-management/update/' . $this->userId;
	}


	private function storedColour(): string
	{
		$statement = TestDatabase::pdo()->prepare('SELECT `' . UserTableMap::COL_COLOR . '` FROM `' . UserTableMap::TABLE_NAME . '` WHERE `' . UserTableMap::COL_ID . '` = ?');
		$statement->execute([$this->userId]);

		return (string) $statement->fetchColumn();
	}
}
