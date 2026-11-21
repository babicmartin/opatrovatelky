<?php declare(strict_types=1);

namespace Tests\Support\PHPUnit;

use Dom\HTMLDocument;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Response;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;

trait ConfirmedFormWorkflowTrait
{
	use PresenterWorkflowTrait;


	/** @param array<string, mixed> $params */
	private function submitConfirmedCreate(string $name, string $button, array $params = [], ?string $html = null): Response
	{
		$params += ['action' => 'default'];
		$html ??= $this->renderPresenter($name, $params);
		$form = $this->extractFormBySubmitLabel($html, $button);

		return $this->runFormRequest($name, 'POST', $params, [
			'_do' => $this->extractHiddenInput($form, '_do'),
			'_token_' => $this->extractHiddenInput($form, '_token_'),
			'create' => $button,
		]);
	}


	/**
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $post
	 */
	private function runFormRequest(string $presenterName, string $method, array $params, array $post = [], bool $sameSite = true): Response
	{
		$this->replaceHttpRequest($method, $post, $sameSite);

		$presenter = $this->getContainer()
			->getByType(IPresenterFactory::class)
			->createPresenter($presenterName);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		return $presenter->run(new ApplicationRequest($presenterName, $method, $params, $post));
	}

	/**
	 * @param array<string, mixed> $post
	 */
	private function replaceHttpRequest(string $method, array $post, bool $sameSite): void
	{
		$request = new HttpRequest(
			new UrlScript('https://example.test/admin/'),
			$post,
			[],
			$sameSite ? [Helpers::StrictCookieName => '1'] : [],
			['Sec-Fetch-Site' => $sameSite ? 'same-origin' : 'cross-site'],
			$method,
		);

		$this->getContainer()->removeService('http.request');
		$this->getContainer()->addService('http.request', $request);
		$this->getContainer()->getByType(IResponse::class)->setCode(IResponse::S200_OK);
	}

	private function extractFormBySubmitLabel(string $html, string $label): string
	{
		if (preg_match_all('/<form\b[^>]*>.*?<\/form>/is', $html, $matches) !== false) {
			foreach ($matches[0] as $formHtml) {
				if (str_contains($formHtml, $label)) {
					return $formHtml;
				}
			}
		}

		self::fail('Form with submit "' . $label . '" was not rendered.');
	}

	/** @return array{_do: string, _token_: string, delete: string} */
	private function readDeleteForm(string $html, string $formId): array
	{
		$form = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8')->getElementById($formId);
		self::assertNotNull($form, 'Missing form ' . $formId);
		self::assertSame('form', $form->localName);
		self::assertSame('post', strtolower($form->getAttribute('method') ?? ''));
		$button = $form->querySelector('input[type="submit"][name="delete"]');
		self::assertNotNull($button);
		self::assertStringContainsString('confirm(', $button->getAttribute('onclick') ?? '');
		$signal = $form->querySelector('input[type="hidden"][name="_do"]')?->getAttribute('value');
		$token = $form->querySelector('input[type="hidden"][name="_token_"]')?->getAttribute('value');
		self::assertIsString($signal);
		self::assertNotSame('', $signal);
		self::assertIsString($token);
		self::assertNotSame('', $token);

		return ['_do' => $signal, '_token_' => $token, 'delete' => $button->getAttribute('value') ?? ''];
	}

	private function extractHiddenInput(string $html, string $name): string
	{
		$quotedName = preg_quote($name, '/');
		if (preg_match('/<input\b(?=[^>]*\bname="' . $quotedName . '")(?=[^>]*\bvalue="([^"]*)")[^>]*>/i', $html, $matches) !== 1) {
			self::fail('Hidden input "' . $name . '" was not rendered.');
		}

		return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
	}
}
