<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Todo\TodoUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Todo\TodoUpdate\TodoUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\TodoClientRepository;
use App\Model\Utils\Date\DateService;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class TodoUpdateFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private TodoClientRepository $todoClientRepository,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $todo
	 * @param array<int, string> $familyOptions
	 * @param array<int, string> $babysitterOptions
	 * @param array<int, string> $userOptions
	 * @param array<int, string> $statusOptions
	 * @param callable(TodoUpdateForm): void $onSuccess
	 */
	public function create(
		array $todo,
		array $familyOptions,
		array $babysitterOptions,
		array $userOptions,
		array $statusOptions,
		callable $onSuccess,
	): Form {
		if (!$this->canAccessTodo((int) $todo['id'])) {
			throw new ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'todo-update-form js-autosave-form');

		$form->addHidden('id', (string) $todo['id']);
		$form->addSelect('familyId', 'Rodina', $familyOptions)
			->setDefaultValue((int) $todo['familyId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('babysitterId', 'Opatrovateľka', $babysitterOptions)
			->setDefaultValue((int) $todo['babysitterId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('todoFromUser', 'Úlohu zadal', $userOptions)
			->setDefaultValue((int) $todo['todoFromUser'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('todoToUser1', 'Úlohu spracováva', $userOptions)
			->setDefaultValue((int) $todo['todoToUser1'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('todoToUser2', 'Úlohu spracováva', $userOptions)
			->setDefaultValue((int) $todo['todoToUser2'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addText('todoCreated', 'Dátum vytvorenia')
			->setDefaultValue($todo['todoCreated'] instanceof \DateTimeImmutable ? $todo['todoCreated']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('todoDeadline', 'Deadline spracovania')
			->setDefaultValue($todo['todoDeadline'] instanceof \DateTimeImmutable ? $todo['todoDeadline']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $todo['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addText('title', 'Názov úlohy')
			->setDefaultValue((string) $todo['title'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addTextArea('description', 'Popis')
			->setDefaultValue((string) $todo['description'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h200');
		$form->addTextArea('answer', 'Odpoveď')
			->setDefaultValue((string) $todo['answer'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h300');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->canAccessTodo((int) $values->id)) {
				throw new ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new TodoUpdateForm(
				(int) $values->id,
				(int) $values->familyId,
				(int) $values->babysitterId,
				(int) $values->todoFromUser,
				(int) $values->todoToUser1,
				(int) $values->todoToUser2,
				$this->dateService->tryCreateFromUserInput((string) $values->todoCreated),
				$this->dateService->tryCreateFromUserInput((string) $values->todoDeadline),
				(int) $values->status,
				(string) $values->title,
				(string) $values->description,
				(string) $values->answer,
			));
		};

		return $form;
	}

	private function canAccessTodo(int $id): bool
	{
		if (!$this->user->isAllowed(Resource::TODO->value)) {
			return false;
		}

		$userId = $this->user->isLoggedIn() ? (int) $this->user->getId() : null;
		return $this->todoClientRepository->getItemForUser(
			$id,
			$userId,
			$this->user->isAllowed(Resource::TODO_VIEW_ALL->value),
		) !== null;
	}
}
