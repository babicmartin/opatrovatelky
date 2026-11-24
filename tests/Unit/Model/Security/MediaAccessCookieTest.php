<?php declare(strict_types=1);

namespace Tests\Unit\Model\Security;

use App\Model\Security\MediaAccessCookie;
use Nette\Http\IResponse;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\PHPUnit\TestCase;

final class MediaAccessCookieTest extends TestCase
{
	private const string Secret = 'unit0test0secret';

	public function testEmptySecretNeverSendsTheCookie(): void
	{
		$response = $this->createMock(IResponse::class);
		$response->expects(self::never())->method('setCookie');

		$cookie = new MediaAccessCookie('', $this->request(), $response);
		$cookie->issue();
		$cookie->sync(true);
	}

	public function testLoggedInUserWithValidCookieIsLeftAlone(): void
	{
		$response = $this->createMock(IResponse::class);
		$response->expects(self::never())->method('setCookie');
		$response->expects(self::never())->method('deleteCookie');

		$this->cookie($response, self::Secret)->sync(true);
	}

	public function testLoggedInUserWithoutCookieReceivesIt(): void
	{
		$response = $this->createMock(IResponse::class);
		$this->expectIssued($response);

		$this->cookie($response, null)->sync(true);
	}

	public function testLoggedInUserWithOutdatedCookieReceivesTheCurrentOne(): void
	{
		$response = $this->createMock(IResponse::class);
		$this->expectIssued($response);

		$this->cookie($response, 'previous0secret')->sync(true);
	}

	public function testGuestWithCookieLosesIt(): void
	{
		$response = $this->createMock(IResponse::class);
		$response->expects(self::never())->method('setCookie');
		$response->expects(self::once())->method('deleteCookie')->with(MediaAccessCookie::Name, '/crm/');

		$this->cookie($response, self::Secret)->sync(false);
	}

	public function testGuestWithoutCookieGetsNoHeader(): void
	{
		$response = $this->createMock(IResponse::class);
		$response->expects(self::never())->method('setCookie');
		$response->expects(self::never())->method('deleteCookie');

		$this->cookie($response, null)->sync(false);
	}

	private function expectIssued(IResponse&MockObject $response): void
	{
		$response->expects(self::never())->method('deleteCookie');
		$response->expects(self::once())->method('setCookie')
			->with(MediaAccessCookie::Name, self::Secret, '1 day', '/crm/', null, null, true);
	}

	private function cookie(IResponse $response, ?string $current): MediaAccessCookie
	{
		return new MediaAccessCookie(self::Secret, $this->request($current), $response);
	}

	private function request(?string $current = null): Request
	{
		return new Request(
			new UrlScript('http://example.test/crm/babysitter', '/crm/index.php'),
			cookies: $current === null ? [] : [MediaAccessCookie::Name => $current],
		);
	}
}
