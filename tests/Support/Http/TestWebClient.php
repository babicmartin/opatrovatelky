<?php declare(strict_types=1);

namespace Tests\Support\Http;

use Dom\Element;
use Dom\HTMLDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Process\Process;

final class TestWebClient
{
	private Process $server;
	private Client $client;
	private CookieJar $cookies;


	public function __construct(?int $uploadLimitMiB = null)
	{
		$this->cookies = new CookieJar();
		$root = dirname(__DIR__, 3);
		$uploadOptions = $uploadLimitMiB === null ? [] : [
			'-d', 'upload_max_filesize=' . $uploadLimitMiB . 'M',
			'-d', 'post_max_size=' . ($uploadLimitMiB + 1) . 'M',
		];
		$this->server = new Process([
			PHP_BINARY, '-d', 'session.save_path=' . $root . '/temp/test-sessions',
			...$uploadOptions,
			'-S', '127.0.0.1:0', '-t', $root . '/www',
		], $root, [
			'APP_ENV' => 'test',
			'TEST_DATABASE_DSN' => $_ENV['TEST_DATABASE_DSN'],
			'TEST_DATABASE_USER' => $_ENV['TEST_DATABASE_USER'],
			'TEST_DATABASE_PASSWORD' => $_ENV['TEST_DATABASE_PASSWORD'],
		]);
		$this->server->start();

		$deadline = microtime(true) + 10;
		do {
			if (preg_match('~Development Server \((http://127\.0\.0\.1:\d+)\) started~', $this->server->getErrorOutput(), $matches)) {
				$this->client = new Client([
					'base_uri' => $matches[1],
					'cookies' => $this->cookies,
					'allow_redirects' => false,
					'http_errors' => false,
					'timeout' => 10,
				]);
				return;
			}
			usleep(10_000);
		} while ($this->server->isRunning() && microtime(true) < $deadline);

		Assert::fail('Test HTTP server failed to start. ' . $this->server->getErrorOutput());
	}


	public function get(string $path): ResponseInterface
	{
		return $this->client->get($path);
	}


	/**
	 * @param array<string, string> $fields
	 * @param array<string, string> $headers
	 */
	public function post(string $path, array $fields, array $headers = []): ResponseInterface
	{
		return $this->client->post($path, ['form_params' => $fields, 'headers' => $headers]);
	}


	/**
	 * Submits the form with its hidden and submit fields. A value in $fields wins over the rendered one,
	 * so a test can forge a hidden field the way a tampered request would.
	 * @param array<string, string> $fields
	 */
	public function submitForm(string $path, string $formId, array $fields = []): ResponseInterface
	{
		[$action, $defaults] = $this->readForm($path, $formId);
		return $this->post($action, $fields + $defaults);
	}


	public function submitUpload(string $path, string $formId, string $filename, StreamInterface $contents, bool $ajax = false): ResponseInterface
	{
		[$action, $fields, $form] = $this->readForm($path, $formId);
		Assert::assertSame('multipart/form-data', $form->getAttribute('enctype'));
		$fileField = $form->querySelector('input[type=file]')?->getAttribute('name');
		Assert::assertIsString($fileField);
		$parts = [];
		foreach ($fields as $name => $value) {
			$parts[] = ['name' => $name, 'contents' => $value];
		}
		$parts[] = ['name' => $fileField, 'filename' => $filename, 'contents' => $contents];
		return $this->client->post($action, [
			'multipart' => $parts,
			'headers' => $ajax ? ['X-Requested-With' => 'XMLHttpRequest'] : [],
			'expect' => false,
			// The PHP 8.5 CLI server on Windows buffers the complete 200 MiB multipart request.
			'timeout' => 900,
		]);
	}


	/** @return array{string, array<string, string>, Element} */
	private function readForm(string $path, string $formId): array
	{
		$page = $this->get($path);
		Assert::assertSame(200, $page->getStatusCode(), $path);
		$html = HTMLDocument::createFromString((string) $page->getBody(), LIBXML_NOERROR, 'UTF-8');
		$form = $html->getElementById($formId);
		Assert::assertNotNull($form, 'Missing form ' . $formId);
		Assert::assertSame('post', strtolower($form->getAttribute('method') ?? ''));
		$fields = [];
		foreach ($form->querySelectorAll('input[type=hidden], input[type=submit], button[type=submit]') as $input) {
			$name = $input->getAttribute('name');
			if ($name !== null) {
				$fields[$name] = $input->getAttribute('value') ?? '';
			}
		}

		$action = UriResolver::resolve(new Uri($path), new Uri($form->getAttribute('action') ?? ''));
		return [(string) $action, $fields, $form];
	}


	public function login(string $email, string $password): ResponseInterface
	{
		return $this->submitForm('/login/', 'frm-login-form', ['email' => $email, 'password' => $password]);
	}


	/** Drops one cookie the way a browser does when it expires or the user clears it. */
	public function forgetCookie(string $name): void
	{
		$before = $this->cookies->count();
		$this->cookies->clear('127.0.0.1', '/', $name);
		Assert::assertSame($before - 1, $this->cookies->count(), 'Cookie ' . $name . ' was not stored.');
	}


	public function stop(): void
	{
		$this->server->stop();
	}
}
