<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Document;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\FileRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\FileTableMap;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Validator\ColorValidator;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Form\ConfirmedDeleteFormFactory;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\FileUpload;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Nette\Utils\Strings;

/**
 * Documents of one record (a babysitter, a family, a partner, an agency, a turnus): list, download,
 * row form, upload and delete. A module adds only what differs: its permission, folder, audit context and template.
 *
 * Every entry point refuses a role without the permission and a page that did not load a record,
 * and reaches a document only through the record it belongs to.
 */
abstract class DocumentsControl extends Control
{
	private const int MAX_FILE_SIZE = 20_971_520;
	private const array ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docs', 'docx'];
	private const array ALLOWED_CONTENT_TYPES = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/octet-stream',
	];
	private const int AUDIT_NOTICE_LENGTH = 100;

	private int $ownerId = 0;

	private string $dir = '';

	private string $title = 'Dokumenty';

	/** @var list<array<string, mixed>>|null */
	private ?array $documents = null;

	public function __construct(
		private readonly FileRepository $fileRepository,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly ConfirmedDeleteFormFactory $confirmedDeleteFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly User $user,
		private readonly DateService $dateService,
		private readonly ChangeAuditLogger $changeAuditLogger,
		private readonly ColorValidator $colorValidator,
	) {
	}

	/** Permission a user needs for the documents of this module. */
	abstract protected function getRequiredResource(): Resource;

	/** Context of the change log and of the autosave definition, for example `documents.babysitter`. */
	abstract protected function getAuditContext(): string;

	/** CSS class of the row form; the autosave script and the styles select the form by it. */
	abstract protected function getFormClass(): string;

	abstract protected function getTemplateFile(): string;

	/** A module whose page offers no status for a document neither shows nor writes it. */
	protected function hasStatus(): bool
	{
		return true;
	}

	protected function getDeleteConfirmMessage(): string
	{
		return 'Naozaj chcete vymazať tento dokument?';
	}

	/**
	 * The record the documents belong to. The presenter passes the ID it has verified, never one from the request.
	 */
	protected function setOwner(string $dir, int $ownerId, ?string $title = null): static
	{
		$this->dir = $dir;
		$this->ownerId = $ownerId;
		$this->title = $title ?? $this->title;
		$this->documents = null;

		return $this;
	}

	public function render(): void
	{
		$this->assertCanManage();

		$template = $this->getTemplate();
		$template->setFile($this->getTemplateFile());
		$template->title = $this->title;
		$template->documents = $this->getDocuments();
		$template->statusOptions = $this->hasStatus() ? $this->fileRepository->findStatusOptions() : [];
		$template->typeImageBasePath = $this->storageDirProvider->getDocumentTypeImages();
		$template->canManage = $this->user->isAllowed($this->getRequiredResource()->value);
		$template->maxFileSize = self::MAX_FILE_SIZE;
		$template->render();
	}

	public function handleDownload(int $id): void
	{
		$this->assertCanManage();

		$document = $this->getDocument($id);
		$path = $this->getPrivateDocumentDir() . '/' . $document['name'];
		if (!is_file($path)) {
			$this->getPresenter()->error('Súbor neexistuje.', 404);
		}

		$this->getPresenter()->sendResponse(new FileResponse($path, (string) $document['name']));
	}

	/**
	 * @return Multiplier<Form>
	 */
	protected function createComponentDocumentForm(): Multiplier
	{
		$this->assertCanManage();

		return new Multiplier(function (string $id): Form {
			$document = $this->getDocument((int) $id);

			$form = $this->baseFormFactory->create();
			$form->getElementPrototype()->setAttribute('class', $this->getFormClass() . ' js-autosave-form');
			// Carried for the autosave script only; the handler below never reads it.
			$form->addHidden('id', (string) $document['id']);
			$form->addText('notice', $document['upload'])
				->setDefaultValue((string) $document['notice'])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
			$form->addText('validFrom', 'Platnosť od')
				->setDefaultValue($document['validFrom'] instanceof \DateTimeImmutable ? $document['validFrom']->format('d.m.Y') : '')
				->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
				->setHtmlAttribute('autocomplete', 'off');
			$form->addText('validTo', 'Platnosť do')
				->setDefaultValue($document['validTo'] instanceof \DateTimeImmutable ? $document['validTo']->format('d.m.Y') : '')
				->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
				->setHtmlAttribute('autocomplete', 'off');
			if ($this->hasStatus()) {
				$form->addSelect('status', 'Status', $this->fileRepository->findStatusOptions())
					->setDefaultValue((int) $document['status'])
					->setHtmlAttribute('class', 'form-select updateSelect js-autosave-control');
			}
			$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

			// Save the document verified above, never the one named by the hidden field of the request.
			$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($document): void {
				$this->assertCanManage();

				$documentId = (int) $document['id'];
				$presenter = $this->getPresenter();
				if ($presenter instanceof AdminPresenter) {
					$presenter->tryHandleAutosavePartialRequest($documentId);
				}

				$changed = [
					'notice' => (string) $values->notice,
					'validFrom' => $this->dateService->tryCreateFromUserInput((string) $values->validFrom),
					'validTo' => $this->dateService->tryCreateFromUserInput((string) $values->validTo),
				];
				if ($this->hasStatus()) {
					$changed['status'] = (int) $values->status;
				}

				$this->fileRepository->updateDocument($documentId, $changed);
				$this->logDocumentUpdated($document, $changed);

				if ($this->getPresenter()->isAjax()) {
					$this->getPresenter()->sendJson(['success' => true]);
				}

				$this->redirect('this');
			};

			return $form;
		});
	}

	/**
	 * Deleting is a submitted form, not a link: an address that deletes can be pasted, linked or prefetched.
	 *
	 * @return Multiplier<Form>
	 */
	protected function createComponentDeleteForm(): Multiplier
	{
		$this->assertCanManage();

		return new Multiplier(function (string $id): Form {
			$document = $this->getDocument((int) $id);

			return $this->confirmedDeleteFormFactory->create($this->getDeleteConfirmMessage(), function () use ($document): void {
				$this->assertCanManage();

				$documentId = (int) $document['id'];
				$this->fileRepository->softDelete($documentId);
				$this->changeAuditLogger->logDocumentDeleted($this->getAuditContext(), $documentId, (string) $document['name'], $this->dir, $this->ownerId);
				$this->redirect('this');
			});
		});
	}

	protected function createComponentUploadForm(): Form
	{
		$this->assertCanManage();

		$form = $this->baseFormFactory->create();
		$form->addUpload('fileAll')
			->setHtmlAttribute('id', 'fileAll')
			->setHtmlAttribute('data-max-size', (string) self::MAX_FILE_SIZE)
			->setHtmlAttribute('data-allowed-extensions', implode(',', self::ALLOWED_EXTENSIONS));
		$form->addSubmit('upload', '')
			->setHtmlAttribute('class', 'd-none js-upload-submit');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$this->assertCanManage();

			/** @var FileUpload $upload */
			$upload = $values->fileAll;
			if (!$upload->isOk() || !$upload->hasFile()) {
				$this->redirect('this');
			}

			$extension = strtolower(pathinfo($upload->getSanitizedName(), PATHINFO_EXTENSION));
			if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
				$form->addError('Povolené sú iba tieto koncovky: ' . implode(', ', self::ALLOWED_EXTENSIONS));
				return;
			}

			if ($upload->getSize() > self::MAX_FILE_SIZE) {
				$form->addError('Maximálna veľkosť súboru je 20 MB.');
				return;
			}

			$contentType = (string) $upload->getContentType();
			if ($contentType !== '' && !in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
				$form->addError('Nepovolený typ súboru.');
				return;
			}

			$type = $extension === 'doc' || $extension === 'docx' ? 'docs' : $extension;
			$name = Strings::webalize(pathinfo($upload->getSanitizedName(), PATHINFO_FILENAME));
			$fileName = ($name !== '' ? $name : 'document') . '-' . Random::generate(8) . '.' . $extension;
			$targetDir = $this->getPrivateDocumentDir();

			FileSystem::createDir($targetDir);
			$upload->move($targetDir . '/' . $fileName);
			$documentId = $this->fileRepository->insertDocument($this->dir, $this->ownerId, $fileName, $type, (int) $this->user->getId());
			$this->changeAuditLogger->logDocumentUploaded($this->getAuditContext(), $documentId, $fileName, $this->dir, $this->ownerId, [
				'file_type' => $type,
				'extension' => $extension,
				'size_bytes' => $upload->getSize(),
			]);
			$this->redirect('this');
		};

		return $form;
	}

	private function assertCanManage(): void
	{
		if (!$this->user->isAllowed($this->getRequiredResource()->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}

		// Attached to a page that loaded no record: there is nothing to list, change or upload to.
		if ($this->ownerId <= 0 || $this->dir === '') {
			$this->getPresenter()->error('Záznam neexistuje.', 404);
		}
	}

	/**
	 * Documents of the record. The colour of the uploader is free text of that user's profile,
	 * so anything that is not a colour is dropped before it reaches a template.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function getDocuments(): array
	{
		return $this->documents ??= array_map(function (array $document): array {
			$document['userColor'] = $this->colorValidator->isColor((string) $document['userColor']) ? (string) $document['userColor'] : '';

			return $document;
		}, $this->fileRepository->findDocuments($this->dir, $this->ownerId));
	}

	/**
	 * A document is found only among the documents of the record; an ID of another record is a 404.
	 *
	 * @return array<string, mixed>
	 */
	private function getDocument(int $id): array
	{
		foreach ($this->getDocuments() as $document) {
			if ((int) $document['id'] === $id) {
				return $document;
			}
		}

		$this->getPresenter()->error('Dokument neexistuje.', 404);
	}

	/**
	 * The whole form saved at once leaves the same kind of trace as a field saved by autosave.
	 *
	 * @param array<string, mixed> $before
	 * @param array<string, mixed> $after
	 */
	private function logDocumentUpdated(array $before, array $after): void
	{
		$old = $this->describeDocument($before);
		$new = $this->describeDocument($after);
		if ($old === $new) {
			return;
		}

		$documentId = (int) $before['id'];
		$this->changeAuditLogger->logUpdated(
			$this->getAuditContext(),
			FileTableMap::TABLE_NAME,
			$documentId,
			$this->formatAuditLabel($old),
			$this->formatAuditLabel($new),
			[
				'document_id' => $documentId,
				'document_dir' => $this->dir,
				'owner_id' => $this->ownerId,
			],
		);
	}

	/**
	 * @param array<string, mixed> $values
	 * @return array{notice: string, validFrom: string, validTo: string, status: string}
	 */
	private function describeDocument(array $values): array
	{
		$formatDate = static fn (mixed $date): string => $date instanceof \DateTimeImmutable ? $date->format('d.m.Y') : '';

		return [
			'notice' => (string) ($values['notice'] ?? ''),
			'validFrom' => $formatDate($values['validFrom'] ?? null),
			'validTo' => $formatDate($values['validTo'] ?? null),
			'status' => $this->hasStatus() ? (string) (int) ($values['status'] ?? 0) : '',
		];
	}

	/**
	 * @param array{notice: string, validFrom: string, validTo: string, status: string} $description
	 */
	private function formatAuditLabel(array $description): string
	{
		return implode(' · ', array_filter([
			'poznámka: ' . mb_substr($description['notice'], 0, self::AUDIT_NOTICE_LENGTH),
			'platnosť od: ' . ($description['validFrom'] !== '' ? $description['validFrom'] : '—'),
			'platnosť do: ' . ($description['validTo'] !== '' ? $description['validTo'] : '—'),
			$description['status'] !== '' ? 'stav: ' . $description['status'] : '',
		], static fn (string $part): bool => $part !== ''));
	}

	private function getPrivateDocumentDir(): string
	{
		return $this->directoryProvider->getRootDir() . '/' . $this->storageDirProvider->getPrivateDocuments() . '/' . $this->dir . '/' . $this->ownerId;
	}
}
