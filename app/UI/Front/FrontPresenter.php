<?php declare(strict_types=1);

namespace App\UI\Front;

use App\UI\Front\Control\GoogleMetaData\GoogleMetaDataControl;
use App\UI\Front\Control\GoogleMetaData\GoogleMetaDataControlFactory;
use Nette\Application\UI\Presenter;
use Nette\DI\Attributes\Inject;

abstract class FrontPresenter extends Presenter
{
	private const SupportedLocales = ['sk', 'en', 'de'];

	#[Inject]
	public GoogleMetaDataControlFactory $googleMetaDataControlFactory;

	/** @persistent */
	public string $locale = 'sk';

	protected function startup(): void
	{
		parent::startup();

		if (!in_array($this->locale, self::SupportedLocales, true)) {
			$this->locale = 'sk';
		}
	}


	protected function beforeRender(): void
	{
		parent::beforeRender();

		$this->template->locale = $this->locale;
		$this->template->supportedLocales = self::SupportedLocales;

		$locale = $this->locale;
		$this->template->addFilter('translate', function (object $row, string $field) use ($locale): string {
			$translatedField = match ($locale) {
				'en' => $field . '_eng',
				'de' => $field . '_de',
				default => null,
			};

			if ($translatedField !== null && isset($row->{$translatedField}) && $row->{$translatedField} !== '') {
				return (string) $row->{$translatedField};
			}

			return (string) $row->{$field};
		});
	}


	public function getLocaleLink(string $targetLocale): string
	{
		return $this->link('this', ['locale' => $targetLocale]);
	}


	protected function createComponentGoogleMetaData(): GoogleMetaDataControl
	{
		return $this->googleMetaDataControlFactory->create();
	}


	protected function getGoogleMetaDataControl(): GoogleMetaDataControl
	{
		return $this['googleMetaData'];
	}
}
