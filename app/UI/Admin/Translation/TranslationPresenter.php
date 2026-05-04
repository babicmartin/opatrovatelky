<?php declare(strict_types=1);

namespace App\UI\Admin\Translation;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\TranslateRepository;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Form\Translation\TranslationUpdate\TranslationUpdateFormFactory;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

final class TranslationPresenter extends AdminPresenter
{
	/** @var list<array{id:int,slovak:string,german:string}> */
	private array $translations = [];

	public function __construct(
		private readonly TranslateRepository $translateRepository,
		private readonly TranslationUpdateFormFactory $translationUpdateFormFactory,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::TRANSLATION->value;
	}

	public function actionDefault(): void
	{
		$this->translations = $this->translateRepository->findRows();
		$this->template->translations = $this->translations;
	}

	/**
	 * @return Multiplier<Form>
	 */
	protected function createComponentTranslationForm(): Multiplier
	{
		return new Multiplier(function (string $id): Form {
			$translation = $this->findTranslation((int) $id);

			return $this->translationUpdateFormFactory->create(
				$translation,
				$this->translationUpdateFormSucceeded(...),
			);
		});
	}

	/**
	 * @param array{german:mixed} $data
	 */
	private function translationUpdateFormSucceeded(int $id, array $data): void
	{
		if (!$this->getUser()->isAllowed(Resource::TRANSLATION->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->translateRepository->updateGerman($id, $data);

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Preklad bol uložený.', 'success');
		$this->redirect('this');
	}

	/**
	 * @return array{id:int,slovak:string,german:string}
	 */
	private function findTranslation(int $id): array
	{
		foreach ($this->translations === [] ? $this->translateRepository->findRows() : $this->translations as $translation) {
			if ($translation['id'] === $id) {
				return $translation;
			}
		}

		$this->error('Preklad neexistuje.', 404);
	}
}
