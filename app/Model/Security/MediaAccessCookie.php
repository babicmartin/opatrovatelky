<?php declare(strict_types=1);

namespace App\Model\Security;

use Nette\Http\IRequest;
use Nette\Http\IResponse;

/**
 * Cookie, podľa ktorej Apache vydáva fotky z www/img/opatrovatelka a www/img/users.
 * Hodnota musí byť rovnaká ako v súboroch .htaccess v oboch priečinkoch.
 */
final class MediaAccessCookie
{
	public const string Name = 'crm_media';
	private const string Lifetime = '1 day';

	public function __construct(
		#[\SensitiveParameter]
		private readonly string $secret,
		private readonly IRequest $request,
		private readonly IResponse $response,
	) {
	}

	public function issue(): void
	{
		if ($this->secret === '') {
			return;
		}

		// SameSite=Lax dopĺňa Nette\Http\Response ako predvolenú hodnotu, Secure riadi http.cookieSecure.
		$this->response->setCookie(
			self::Name,
			$this->secret,
			self::Lifetime,
			$this->getPath(),
			httpOnly: true,
		);
	}

	/**
	 * Prihlásenému doplní chýbajúcu alebo neplatnú cookie, neprihlásenému ju zmaže.
	 */
	public function sync(bool $loggedIn): void
	{
		$current = $this->request->getCookie(self::Name);

		if ($loggedIn) {
			if ($current !== $this->secret) {
				$this->issue();
			}

			return;
		}

		if ($current !== null) {
			$this->clear();
		}
	}

	public function clear(): void
	{
		$this->response->deleteCookie(self::Name, $this->getPath());
	}

	/**
	 * Cesta musí pokrývať aj stránky aplikácie, inak by startup nevidel, či ju prehliadač má.
	 */
	private function getPath(): string
	{
		return $this->request->getUrl()->getBasePath();
	}
}
