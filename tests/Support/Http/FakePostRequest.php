<?php declare(strict_types=1);

namespace Tests\Support\Http;

use Nette\Http\IRequest;
use Nette\Http\UrlScript;

/**
 * Minimal POST IRequest for driving request-bound services (autosave) in tests.
 */
final class FakePostRequest implements IRequest
{
	/**
	 * @param array<string, mixed> $post
	 */
	public function __construct(private readonly array $post)
	{
	}

	public function getUrl(): UrlScript
	{
		return new UrlScript('https://example.test/');
	}

	public function getQuery(?string $key = null): mixed
	{
		return $key === null ? [] : null;
	}

	public function getPost(?string $key = null): mixed
	{
		return $key === null ? $this->post : ($this->post[$key] ?? null);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getFile(string $key): ?array
	{
		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getFiles(): array
	{
		return [];
	}

	public function getCookie(string $key): mixed
	{
		return null;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCookies(): array
	{
		return [];
	}

	public function getMethod(): string
	{
		return self::Post;
	}

	public function isMethod(string $method): bool
	{
		return strcasecmp($method, self::Post) === 0;
	}

	public function getHeader(string $header): ?string
	{
		return null;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return [];
	}

	public function isSecured(): bool
	{
		return true;
	}

	public function isAjax(): bool
	{
		return true;
	}

	public function getRemoteAddress(): string
	{
		return '127.0.0.1';
	}

	public function getRemoteHost(): string
	{
		return 'localhost';
	}

	public function getRawBody(): ?string
	{
		return null;
	}
}
