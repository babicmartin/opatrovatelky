<?php declare(strict_types=1);

namespace Tests\Functional;

use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class LoginPresenterSmokeTest extends DatabaseTestCase
{
	public function testLoginPresenterRendersDefaultPage(): void
	{
		$presenterFactory = $this->getContainer()->getByType(IPresenterFactory::class);
		$presenter = $presenterFactory->createPresenter('Login:Login');
		if (!$presenter instanceof Presenter) {
			self::fail('Login presenter must be a UI presenter.');
		}
		$presenter->autoCanonicalize = false;

		$response = $presenter->run(new ApplicationRequest('Login:Login', 'GET', ['action' => 'default']));

		self::assertInstanceOf(TextResponse::class, $response);

		$level = ob_get_level();
		ob_start();
		try {
			$response->send(
				$this->getContainer()->getByType(IRequest::class),
				$this->getContainer()->getByType(IResponse::class),
			);
			$html = (string) ob_get_clean();
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}

		self::assertStringContainsString('Prihlásenie', $html);
	}
}
