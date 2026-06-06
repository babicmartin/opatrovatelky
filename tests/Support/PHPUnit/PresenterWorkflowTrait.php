<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use App\Model\Enum\UserRole\UserRole;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Tests\Support\Database\TestDatabase;

/**
 * In-process presenter driving: login as a role, run a presenter and capture its HTML.
 * Shared by smoke, snapshot and in-process E2E tests so the request/response plumbing
 * lives in one place.
 *
 * Host class must provide getContainer(): Nette\DI\Container (see ContainerTestCase).
 */
trait PresenterWorkflowTrait
{
	protected function loginAs(UserRole $role, ?int $id = null): int
	{
		$email = strtolower($role->value) . '.workflow@example.test';
		$id ??= TestDatabase::createUser([
			'email' => $email,
			'permission' => $role->getPermissionId(),
		]);

		$this->getContainer()->getByType(User::class)->login(new SimpleIdentity($id, [$role->value], [
			'email' => $email,
		]));

		return $id;
	}

	protected function logout(): void
	{
		$this->getContainer()->getByType(User::class)->logout(true);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	protected function renderPresenter(string $name, array $params = []): string
	{
		$response = $this->runPresenter($name, $params);

		self::assertInstanceOf(TextResponse::class, $response);

		return $this->sendTextResponse($response);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	protected function runPresenter(string $name, array $params = []): Response
	{
		$params += ['action' => 'default'];

		$presenterFactory = $this->getContainer()->getByType(IPresenterFactory::class);
		$presenter = $presenterFactory->createPresenter($name);
		if (!$presenter instanceof Presenter) {
			self::fail($name . ' presenter must be a UI presenter.');
		}

		$presenter->autoCanonicalize = false;

		return $presenter->run(new ApplicationRequest($name, 'GET', $params));
	}

	protected function sendTextResponse(TextResponse $response): string
	{
		$level = ob_get_level();
		ob_start();
		try {
			$response->send(
				$this->getContainer()->getByType(IRequest::class),
				$this->getContainer()->getByType(IResponse::class),
			);

			return (string) ob_get_clean();
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}
	}
}
