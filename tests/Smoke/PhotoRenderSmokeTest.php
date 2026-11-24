<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\SearchRepository;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\UserTableMap;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * Renders every Latte template that shows a personal photo and pins where the <img> points.
 * Apache serves www/img/opatrovatelka and www/img/users only with the crm_media cookie, so a
 * template that starts reading photos from another directory would publish them again.
 */
final class PhotoRenderSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string LegacyDir = 'img/opatrovatelka';
	private const string UserDir = 'img/users';
	private const string Placeholder = '/img/empty.jpg';
	private const string UserPlaceholder = '/img/users/empty.jpg';

	private string $legacyImage;
	private string $userImage;

	/** @var list<string> */
	private array $createdFiles = [];

	protected function setUp(): void
	{
		parent::setUp();

		$suffix = bin2hex(random_bytes(6));
		$this->legacyImage = 'render-test-legacy-' . $suffix . '.png';
		$this->userImage = 'render-test-user-' . $suffix . '.png';
		$this->createImage(self::LegacyDir, $this->legacyImage);
		$this->createImage(self::UserDir, $this->userImage);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdFiles as $file) {
			FileSystem::delete($file);
		}
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}

	public function testBabysitterListRendersPhotosFromBothGuardedDirectories(): void
	{
		$this->createPerson('Legacyova', $this->legacyImage);
		$this->createPerson('Userova', $this->userImage);
		$this->createPerson('Bezfotky', '');
		$this->createPerson('Stratena', 'missing-file.png');
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter('Admin:Babysitter');

		self::assertSame('/' . self::LegacyDir . '/' . $this->legacyImage, $this->rowPhoto($html, 'Legacyova'));
		self::assertSame('/' . self::UserDir . '/' . $this->userImage, $this->rowPhoto($html, 'Userova'));
		self::assertSame(self::Placeholder, $this->rowPhoto($html, 'Bezfotky'));
		self::assertSame(self::Placeholder, $this->rowPhoto($html, 'Stratena'));
	}

	public function testWorkerListRendersPhotosFromBothGuardedDirectories(): void
	{
		$this->createPerson('Legacyova', $this->legacyImage, 2);
		$this->createPerson('Userova', $this->userImage, 2);
		$this->createPerson('Bezfotky', '', 2);
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter('Admin:Worker');

		self::assertSame('/' . self::LegacyDir . '/' . $this->legacyImage, $this->rowPhoto($html, 'Legacyova'));
		self::assertSame('/' . self::UserDir . '/' . $this->userImage, $this->rowPhoto($html, 'Userova'));
		self::assertSame(self::Placeholder, $this->rowPhoto($html, 'Bezfotky'));
	}

	public function testBabysitterDetailRendersTheProfilePhoto(): void
	{
		$legacyId = $this->createPerson('Legacyova', $this->legacyImage);
		$userId = $this->createPerson('Userova', $this->userImage);
		$emptyId = $this->createPerson('Bezfotky', '');
		$this->loginAs(UserRole::ADMIN);

		self::assertContains(
			'/' . self::LegacyDir . '/' . $this->legacyImage,
			$this->imageSources($this->renderPresenter('Admin:Babysitter', ['action' => 'update', 'id' => $legacyId])),
		);
		self::assertContains(
			'/' . self::UserDir . '/' . $this->userImage,
			$this->imageSources($this->renderPresenter('Admin:Babysitter', ['action' => 'update', 'id' => $userId])),
		);
		$empty = $this->imageSources($this->renderPresenter('Admin:Babysitter', ['action' => 'update', 'id' => $emptyId]));
		self::assertContains(self::UserPlaceholder, $empty);
		self::assertSame([], $this->guardedPhotos($empty, [self::UserPlaceholder]));
	}

	public function testSearchResultsRenderTheBabysitterPhoto(): void
	{
		$this->createPerson('Hladanalegacy', $this->legacyImage);
		$this->createPerson('Hladanauser', $this->userImage);
		$this->createPerson('Hladanabez', '');
		$this->loginAs(UserRole::ADMIN);

		$desktop = $this->dispatchSearch('search-search', SearchRepository::TYPE_BABYSITTER, 'Hladana');
		self::assertIsString($desktop['html']);
		self::assertSame('/' . self::LegacyDir . '/' . $this->legacyImage, $this->rowPhoto($desktop['html'], 'Hladanalegacy'));
		self::assertSame('/' . self::UserDir . '/' . $this->userImage, $this->rowPhoto($desktop['html'], 'Hladanauser'));
		self::assertSame(self::Placeholder, $this->rowPhoto($desktop['html'], 'Hladanabez'));

		$mobile = $this->dispatchSearch('search-searchMobile', SearchRepository::TYPE_BABYSITTER, 'Hladana');
		self::assertIsString($mobile['html']);
		$sources = $this->imageSources($mobile['html']);
		self::assertContains('/' . self::LegacyDir . '/' . $this->legacyImage, $sources);
		self::assertContains('/' . self::UserDir . '/' . $this->userImage, $sources);
		self::assertContains(self::Placeholder, $sources);
	}

	public function testUserManagementAndLayoutRenderTheAvatar(): void
	{
		$withAvatar = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'avatar@example.test',
			UserTableMap::COL_SECOND_NAME => 'Avatarovy',
			UserTableMap::COL_IMAGE => $this->userImage,
		]);
		$withoutAvatar = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'no.avatar@example.test',
			UserTableMap::COL_SECOND_NAME => 'Bezavatara',
			UserTableMap::COL_IMAGE => '',
		]);
		$avatar = '/' . self::UserDir . '/' . $this->userImage;

		$this->loginAs(UserRole::ADMIN, $withAvatar);
		$list = $this->renderPresenter('Admin:UserManagement');
		// once in the layout header for the logged-in user, once in the table row
		self::assertSame(2, count(array_keys($this->imageSources($list), $avatar, true)));
		self::assertContains(self::UserPlaceholder, $this->imageSources($list));
		self::assertContains($avatar, $this->imageSources(
			$this->renderPresenter('Admin:UserManagement', ['action' => 'update', 'id' => $withAvatar]),
		));
		$this->logout();

		$this->loginAs(UserRole::ADMIN, $withoutAvatar);
		$own = $this->imageSources(
			$this->renderPresenter('Admin:UserManagement', ['action' => 'update', 'id' => $withoutAvatar]),
		);
		self::assertSame([], $this->guardedPhotos($own, [self::UserPlaceholder]));
		self::assertGreaterThanOrEqual(2, count(array_keys($own, self::UserPlaceholder, true)));
	}

	public function testGuestLoginPageReferencesNoGuardedPhoto(): void
	{
		$sources = $this->imageSources($this->renderPresenter('Login:Login'));

		self::assertSame(['/img/logo.png'], $sources);
		self::assertSame([], $this->guardedPhotos($sources));
	}

	private function createPerson(string $surname, string $image, int $type = 1): int
	{
		return TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_SURNAME => $surname,
			OpatrovatelkaTableMap::COL_IMAGE => $image,
			OpatrovatelkaTableMap::COL_TYPE => $type,
		]);
	}

	private function createImage(string $directory, string $name): void
	{
		$path = dirname(__DIR__, 2) . '/www/' . $directory . '/' . $name;
		// 1x1 PNG; the templates only need the file to exist
		FileSystem::write($path, (string) base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
			true,
		));
		$this->createdFiles[] = $path;
	}

	/**
	 * @return list<string>
	 */
	private function imageSources(string $html): array
	{
		preg_match_all('~<img\b[^>]*\bsrc="([^"]*)"~i', $html, $matches);

		return $matches[1];
	}

	/**
	 * Photo of the table row that mentions the given surname.
	 */
	private function rowPhoto(string $html, string $surname): string
	{
		preg_match_all('~<tr\b.*?</tr>~is', $html, $rows);
		$matching = array_values(array_filter(
			$rows[0],
			static fn (string $row): bool => str_contains($row, $surname),
		));
		self::assertCount(1, $matching, 'Exactly one row must mention ' . $surname);
		// the photo is the round avatar; the country flag in the same row is a plain <img>
		preg_match_all('~<img\b[^>]*\bclass="rounded-circle"[^>]*>~i', $matching[0], $avatars);
		self::assertCount(1, $avatars[0], 'Row of ' . $surname . ' must show exactly one photo.');
		$sources = $this->imageSources($avatars[0][0]);
		self::assertCount(1, $sources);

		return $sources[0];
	}

	/**
	 * @param list<string> $sources
	 * @param list<string> $except
	 * @return list<string>
	 */
	private function guardedPhotos(array $sources, array $except = []): array
	{
		return array_values(array_filter(
			$sources,
			static fn (string $source): bool => !in_array($source, $except, true)
				&& (str_starts_with($source, '/' . self::LegacyDir . '/') || str_starts_with($source, '/' . self::UserDir . '/')),
		));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function dispatchSearch(string $signal, int $type, string $term): array
	{
		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', new HttpRequest(
			new UrlScript('https://opatrovatelky.local/'),
			[],
			[],
			[Helpers::StrictCookieName => '1'],
			['X-Requested-With' => 'XMLHttpRequest'],
			'GET',
		));
		$container->getByType(IResponse::class)->setCode(IResponse::S200_OK);

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter('Admin:Home');
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		$response = $presenter->run(new ApplicationRequest('Admin:Home', 'GET', [
			'action' => 'default',
			Presenter::SignalKey => $signal,
			'search-type' => $type,
			'search-term' => $term,
		]));

		self::assertInstanceOf(JsonResponse::class, $response);
		$payload = $response->getPayload();
		self::assertIsArray($payload);

		return $payload;
	}
}
