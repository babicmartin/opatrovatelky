<?php declare(strict_types=1);

namespace Tests\Smoke;

use App\UI\Admin\AdminPresenter;
use FilesystemIterator;
use Nette\Application\Responses\RedirectResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * A guest gets into no page of the administration, whatever the page is.
 * What a signed-in role may open is covered by AdminCrudPresenterSmokeTest.
 */
final class GuestAccessSmokeTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string UiDir = __DIR__ . '/../../app/UI';

	/**
	 * Every presenter of the administration, found on disk, so a new one is covered without touching this test.
	 *
	 * @return array<string, array{string}>
	 */
	public static function administrationPresenters(): array
	{
		$presenters = [];
		foreach (glob(self::UiDir . '/Admin/*/*Presenter.php') ?: [] as $file) {
			$name = basename($file, 'Presenter.php');
			$class = 'App\UI\Admin\\' . $name . '\\' . $name . 'Presenter';
			if (is_subclass_of($class, AdminPresenter::class) && !new ReflectionClass($class)->isAbstract()) {
				$presenters[$name] = ['Admin:' . $name];
			}
		}

		return $presenters;
	}

	public function testPresenterListMissesNoAdministrationPresenter(): void
	{
		$found = array_keys(self::administrationPresenters());

		foreach (['Home', 'LoginLog', 'Project', 'Settings', 'Stats', 'Worker'] as $name) {
			self::assertContains($name, $found);
		}

		// A presenter placed deeper than Admin/<Name>/ would slip past the list above.
		$declared = 0;
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::UiDir, FilesystemIterator::SKIP_DOTS));
		foreach ($files as $file) {
			self::assertInstanceOf(SplFileInfo::class, $file);
			if (str_ends_with($file->getFilename(), 'Presenter.php')
				&& preg_match('~\bclass\s+\w+\s+extends\s+\\\\?(?:[\w\\\\]+\\\\)?AdminPresenter\b~', (string) file_get_contents($file->getPathname()))
			) {
				$declared++;
			}
		}

		self::assertCount($declared, $found);
	}

	#[DataProvider('administrationPresenters')]
	public function testGuestIsSentToTheLoginPage(string $presenter): void
	{
		$this->logout();

		$response = $this->runPresenter($presenter);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame('/login/', parse_url($response->getUrl(), PHP_URL_PATH));
	}
}
