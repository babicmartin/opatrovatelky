<?php declare(strict_types=1);

namespace App\UI\Admin;

use App\Model\Security\SecurityAuditLogger;
use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\FileTableMap;
use App\UI\Admin\Control\Layout\Menu\MenuPresenterTrait;
use App\UI\Admin\Control\Layout\Search\SearchPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\FamilyOffcanvas\FamilyOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas\PartnerOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\ProjectOffcanvas\ProjectOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\ToolbarPresenterTrait;
use App\UI\Admin\Control\User\UserProfileImage\UserProfileImagePresenterTrait;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use Nette\Security\SimpleIdentity;

abstract class AdminPresenter extends Presenter
{
	use FamilyOffcanvasPresenterTrait;
	use MenuPresenterTrait;
	use PartnerOffcanvasPresenterTrait;
	use ProjectOffcanvasPresenterTrait;
	use SearchPresenterTrait;
	use ToolbarPresenterTrait;
	use UserProfileImagePresenterTrait;

	private AutosaveFieldUpdateService $autosaveFieldUpdateService;
	private SecurityAuditLogger $securityAuditLogger;
	private Explorer $database;

	public function injectAutosaveFieldUpdateService(AutosaveFieldUpdateService $autosaveFieldUpdateService): void
	{
		$this->autosaveFieldUpdateService = $autosaveFieldUpdateService;
	}

	public function injectDatabase(Explorer $database): void
	{
		$this->database = $database;
	}

	public function injectSecurityAuditLogger(SecurityAuditLogger $securityAuditLogger): void
	{
		$this->securityAuditLogger = $securityAuditLogger;
	}

	protected function getResource(): ?string
	{
		return null;
	}

	protected function getPrivilege(): string
	{
		return 'default';
	}

	protected function startup(): void
	{
		parent::startup();

		$this->getSession()->start();

		if (!$this->getUser()->isLoggedIn() && $this->getName() !== 'Login:Login') {
			$this->redirect('@login', ['storeRequestId' => $this->storeRequest()]);
		}

		$resource = $this->getResource();

		if ($resource !== null && !$this->getUser()->isAllowed($resource, $this->getPrivilege())) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	protected function beforeRender(): void
	{
	}

	protected function createComponentLogoutForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->setAction($this->link(':Login:Login:logout'));
		$form->addSubmit('send', 'Odhlásiť sa');
		$form->onSuccess[] = [$this, 'logoutFormSucceeded'];
		$form->onError[] = function (): void {
			$this->error('Odhlásenie sa nepodarilo.', 403);
		};

		return $form;
	}

	public function logoutFormSucceeded(): void
	{
		if ($this->getUser()->isLoggedIn()) {
			$userId = $this->getUser()->getId();
			$identity = $this->getUser()->getIdentity();
			$identityData = $identity instanceof SimpleIdentity ? $identity->getData() : [];
			$this->securityAuditLogger->log(
				'logout',
				is_int($userId) ? $userId : null,
				isset($identityData['email']) ? (string) $identityData['email'] : null,
			);
			$this->getUser()->logout(true);
			$this->getSession()->regenerateId();
		}

		$this->redirect('@login');
	}

	public function tryHandleAutosavePartialRequest(?int $expectedEntityId = null): void
	{
		if (!$this->isAjax()) {
			return;
		}

		$request = $this->getHttpRequest();
		$isPartialAutosave = $request->getPost('__autosave_context') !== null
			|| $request->getPost('__autosave_field') !== null;

		if ($isPartialAutosave && $expectedEntityId !== null && (int) ($request->getPost('id') ?? 0) !== $expectedEntityId) {
			$this->getHttpResponse()->setCode(400);
			$this->sendJson(['success' => false, 'message' => 'Neplatný autosave záznam.']);
		}

		if ($this->autosaveFieldUpdateService->tryHandleRequest($request)) {
			$this->sendJson(['success' => true]);
		}

		if ($isPartialAutosave) {
			$this->getHttpResponse()->setCode(400);
			$this->sendJson(['success' => false, 'message' => 'Autosave hodnotu sa nepodarilo uložiť.']);
		}
	}

	public function handleAutosavePartial(): void
	{
		$request = $this->getHttpRequest();
		if (!$this->isAjax() || !$request->isMethod('POST')) {
			$this->sendAutosavePartialError(400, 'Neplatný autosave request.');
		}

		if (!$request->isSameSite()) {
			$this->sendAutosavePartialError(403, 'Neplatný pôvod requestu.');
		}

		$context = (string) ($request->getPost('__autosave_context') ?? '');
		$entityId = (int) ($request->getPost('id') ?? 0);
		if ($context === '' || $entityId <= 0) {
			$this->sendAutosavePartialError(400, 'Chýba autosave kontext alebo ID.');
		}

		if (!in_array($context, $this->getAllowedAutosaveContexts(), true)) {
			$this->sendAutosavePartialError(403, 'Autosave kontext nie je povolený.');
		}

		$this->validateAutosavePartialRequest($context, $entityId);

		if (!$this->autosaveFieldUpdateService->tryHandleRequest($request)) {
			$this->sendAutosavePartialError(400, 'Autosave hodnotu sa nepodarilo uložiť.');
		}

		$this->sendJson(['success' => true]);
	}

	/**
	 * @return list<string>
	 */
	protected function getAllowedAutosaveContexts(): array
	{
		return match ($this->getName()) {
			'Admin:Agency' => ['agency.update', 'documents.agency'],
			'Admin:Babysitter' => ['babysitter.main', 'babysitter.address', 'babysitter.education', 'babysitter.profile', 'babysitter.pdf', 'babysitter.workProfile', 'documents.babysitter'],
			'Admin:Country' => ['country.update'],
			'Admin:Family' => ['family.shortInfo', 'family.info', 'family.address', 'documents.family'],
			'Admin:MissingRegistry' => ['missingRegistry.row'],
			'Admin:Partner' => ['partner.update', 'documents.partner'],
			'Admin:Proposal' => ['proposal.update'],
			'Admin:Todo' => ['todo.update'],
			'Admin:Translation' => ['translation.update'],
			'Admin:Turnus' => ['turnus.update', 'turnus.statusA1', 'documents.turnus'],
			'Admin:UserManagement' => ['user.profile', 'user.access'],
			default => [],
		};
	}

	protected function validateAutosavePartialRequest(string $context, int $entityId): void
	{
		if (str_starts_with($context, 'documents.')) {
			$ownerId = $this->getCurrentRouteId();
			if ($ownerId === null) {
				$this->sendAutosavePartialError(400, 'Chýba vlastník dokumentu.');
				return;
			}

			$dirs = match ($context) {
				'documents.agency' => ['agencies'],
				'documents.babysitter' => ['babysitters'],
				'documents.family' => ['families-orders', 'families-contracts'],
				'documents.partner' => ['partners'],
				'documents.turnus' => ['turnus'],
				default => [],
			};
			$this->validateDocumentAutosavePartial($dirs, $ownerId, $entityId);
			return;
		}

		if (in_array($context, $this->getCurrentEntityAutosaveContexts(), true)) {
			$currentId = $this->getCurrentRouteId();
			if ($currentId === null || $currentId !== $entityId) {
				$this->sendAutosavePartialError(400, 'Neplatný autosave záznam.');
			}
		}
	}

	/**
	 * @return list<string>
	 */
	protected function getCurrentEntityAutosaveContexts(): array
	{
		return [
			'agency.update',
			'babysitter.main',
			'babysitter.address',
			'babysitter.education',
			'babysitter.profile',
			'babysitter.pdf',
			'babysitter.workProfile',
			'country.update',
			'family.shortInfo',
			'family.info',
			'family.address',
			'partner.update',
			'proposal.update',
			'todo.update',
			'turnus.update',
			'turnus.statusA1',
		];
	}

	protected function sendAutosavePartialError(int $code, string $message): void
	{
		$this->getHttpResponse()->setCode($code);
		$this->sendJson(['success' => false, 'message' => $message]);
	}

	private function getCurrentRouteId(): ?int
	{
		$id = $this->getParameter('id');

		return is_numeric($id) ? (int) $id : null;
	}

	/**
	 * @param list<string> $dirs
	 */
	private function validateDocumentAutosavePartial(array $dirs, int $ownerId, int $documentId): void
	{
		$paths = array_map(static fn (string $dir): string => $dir . '/' . $ownerId, $dirs);
		$document = $this->database->table(FileTableMap::TABLE_NAME)
			->where(FileTableMap::COL_ID, $documentId)
			->where(FileTableMap::COL_DIR, $paths)
			->where(FileTableMap::COL_ACTIVE, 1)
			->fetch();

		if ($document === null) {
			$this->sendAutosavePartialError(404, 'Dokument neexistuje.');
		}
	}
}
