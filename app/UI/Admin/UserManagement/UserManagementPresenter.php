<?php declare(strict_types=1);

namespace App\UI\Admin\UserManagement;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserAccessUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserPasswordUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Repository\UserRepository;
use App\Model\Security\AccountPasswordService;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\UserTableMap;
use App\Model\Utils\Validator\ImageValidator;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Form\UserManagement\UserProfileUpdate\UserProfileUpdateFormFactory;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\TextInput;
use Nette\Http\FileUpload;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;

final class UserManagementPresenter extends AdminPresenter
{
	private ?ActiveRow $currentUserRow = null;
	private ?int $editableUserId = null;

	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly UserProfileUpdateFormFactory $userProfileUpdateFormFactory,
		private readonly Passwords $passwords,
		private readonly AccountPasswordService $accountPasswords,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly ImageValidator $imageValidator,
		private readonly ChangeAuditLogger $changeAuditLogger,
	) {
		parent::__construct();
	}

	protected function startup(): void
	{
		parent::startup();

		if (!$this->getUser()->isLoggedIn() || !is_int($this->getUser()->getId())) {
			$this->redirect('@login');
		}
	}

	public function actionDefault(): void
	{
		if (!$this->getUser()->isAllowed(Resource::USER_MANAGEMENT->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$users = $this->userRepository->findManagementRows();
		$this->template->users = $users;
		$this->template->editableUserIds = array_values(array_map(
			fn (array $row): int => $row['id'],
			array_filter($users, fn (array $row): bool => $this->canEditUser($row['id'], $row['permission'])),
		));
		$this->template->userImagesPath = $this->storageDirProvider->getUserImages();
		$this->template->emptyUserImagePath = $this->storageDirProvider->getUserImagesEmpty();
		$this->template->canCreateUser = $this->canManageOtherUsers();
	}

	public function actionUpdate(?int $id = null): void
	{
		$user = $this->getCurrentUserRow();
		$userId = (int) $user->{UserTableMap::COL_ID};
		$permission = (int) $user->{UserTableMap::COL_PERMISSION};
		if (!$this->canEditUser($userId, $permission)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$image = (string) ($user->{UserTableMap::COL_IMAGE} ?? '');

		$this->template->profileUser = $this->createUserTemplateData($user);
		$this->template->ownProfile = $userId === $this->getUser()->getId();
		$this->template->canEditAccess = $this->canEditAccess($permission);
		$this->template->imagePath = $image !== ''
			? $this->storageDirProvider->getUserImages() . '/' . $image
			: $this->storageDirProvider->getUserImagesEmpty();
	}

	public function handleCreate(): void
	{
		$this->redirect('default');
	}

	protected function createComponentUserProfileUpdateForm(): Form
	{
		return $this->userProfileUpdateFormFactory->createProfileForm(
			$this->createUserTemplateData($this->getCurrentUserRow()),
			$this->userProfileUpdateFormSucceeded(...),
		);
	}

	protected function createComponentCreateUserForm(): Form
	{
		if (!$this->getUser()->isAllowed(Resource::USER_MANAGEMENT->value) || !$this->canManageOtherUsers()) {
			$this->error('Prístup zamietnutý', 403);
		}

		return $this->createConfirmedCreateForm(
			'Pridať nového užívateľa',
			'Naozaj chcete vytvoriť nového užívateľa?',
			$this->createUserFormSucceeded(...),
			'',
		);
	}

	protected function createComponentUserAccessUpdateForm(): Form
	{
		$user = $this->getCurrentUserRow();
		$permission = (int) $user->{UserTableMap::COL_PERMISSION};
		if (!$this->canEditAccess($permission)) {
			$this->error('Prístup zamietnutý', 403);
		}

		return $this->userProfileUpdateFormFactory->createAccessForm(
			$this->createUserTemplateData($user),
			$this->userRepository->findPermissionOptions($this->isAdmin()),
			$this->userRepository->findActiveOptions(),
			$this->userAccessUpdateFormSucceeded(...),
		);
	}

	protected function createComponentUserPasswordUpdateForm(): Form
	{
		if ($this->getEditableUserId() !== $this->getUser()->getId()) {
			$this->error('Prístup zamietnutý', 403);
		}

		return $this->userProfileUpdateFormFactory->createPasswordForm(
			$this->userPasswordUpdateFormSucceeded(...),
		);
	}

	protected function createComponentUserPasswordResetForm(): Form
	{
		$this->requirePasswordResetPermission();
		return $this->userProfileUpdateFormFactory->createPasswordResetForm($this->userPasswordResetFormSucceeded(...));
	}

	protected function createComponentUserImageUpdateForm(): Form
	{
		return $this->userProfileUpdateFormFactory->createImageForm(
			$this->userImageUpdateFormSucceeded(...),
		);
	}

	private function userProfileUpdateFormSucceeded(UserProfileUpdateForm $form): void
	{
		$userId = $this->getEditableUserId();
		$user = $this->getCurrentUserRow();
		if (!$this->canEditUser($userId, (int) $user->{UserTableMap::COL_PERMISSION})) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest($userId);
		$this->userRepository->updateProfile($userId, $form);

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Profil bol uložený.', 'success');
		$this->redirect('this');
	}

	private function createUserFormSucceeded(Form $form): void
	{
		if (!$this->getUser()->isAllowed(Resource::USER_MANAGEMENT->value) || !$this->canManageOtherUsers()) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->userRepository->createEmptyUser($this->passwords->hash(Random::generate(24)));
		$this->changeAuditLogger->logCreated('user.profile', UserTableMap::TABLE_NAME, $id, 'Používateľ');
		$this->redirect('update', $id);
	}

	private function userAccessUpdateFormSucceeded(UserAccessUpdateForm $form): void
	{
		$userId = $this->getEditableUserId();
		$user = $this->getCurrentUserRow();
		if (!$this->canEditAccess((int) $user->{UserTableMap::COL_PERMISSION})) {
			$this->error('Prístup zamietnutý', 403);
		}

		if ($form->getPermission() === UserRole::ADMIN->getPermissionId() && !$this->isAdmin()) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->tryHandleAutosavePartialRequest($userId);
		$this->userRepository->updateAccess($userId, $form->getPermission(), $form->getActive());

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Práva boli uložené.', 'success');
		$this->redirect('this');
	}

	private function userPasswordUpdateFormSucceeded(Form $form, UserPasswordUpdateForm $values): void
	{
		$userId = $this->getEditableUserId();
		$identity = $this->getUser()->getIdentity();
		if (!$identity instanceof SimpleIdentity || $identity->getId() !== $userId) {
			$this->error('Prístup zamietnutý', 403);
		}

		$version = $this->accountPasswords->changeOwnPassword($userId, $values->currentPassword, $values->getPassword());
		if ($version === null) {
			$currentPassword = $form['currentPassword'];
			assert($currentPassword instanceof TextInput);
			$currentPassword->addError('Aktuálne heslo nie je správne.');
			return;
		}

		$data = $identity->getData();
		$data['sessionVersion'] = $version;
		$this->getUser()->login(new SimpleIdentity($userId, $identity->getRoles(), $data));
		$this->flashMessage('Heslo bolo zmenené. Ostatné zariadenia sa musia znova prihlásiť.', 'success');
		$this->redirect('this');
	}

	private function userPasswordResetFormSucceeded(Form $form, #[\SensitiveParameter] string $newPassword): void
	{
		$permission = $this->requirePasswordResetPermission();
		$actorId = $this->getUser()->getId();
		if (!is_int($actorId) || !$this->accountPasswords->resetPassword($actorId, $this->getEditableUserId(), $permission, $newPassword)) {
			$form->addError('Účet sa medzičasom zmenil. Obnovte stránku.');
			return;
		}

		$this->flashMessage('Heslo bolo resetované. Používateľ sa musí znova prihlásiť.', 'success');
		$this->redirect('this');
	}

	private function requirePasswordResetPermission(): int
	{
		$userId = $this->getEditableUserId();
		$permission = (int) $this->getCurrentUserRow()->{UserTableMap::COL_PERMISSION};
		if ($this->getUser()->getId() === $userId || !$this->canEditUser($userId, $permission)) {
			$this->error('Prístup zamietnutý', 403);
		}
		return $permission;
	}

	private function userImageUpdateFormSucceeded(Form $form, ArrayHash $values): void
	{
		$userId = $this->getEditableUserId();
		$user = $this->getCurrentUserRow();
		if (!$this->canEditUser($userId, (int) $user->{UserTableMap::COL_PERMISSION})) {
			$this->error('Prístup zamietnutý', 403);
		}

		$upload = $values->image;
		if (!$upload instanceof FileUpload || !$upload->isOk()) {
			$form->addError('Obrázok sa nepodarilo nahrať.');
			return;
		}

		$extension = strtolower(pathinfo($upload->getName(), PATHINFO_EXTENSION));
		if (!in_array($extension, ['jpg', 'jpeg', 'png'], true) || !$this->imageValidator->isImage($upload->getTemporaryFile())) {
			$form->addError('Iba obrázky typu .png, .jpg');
			return;
		}

		$fileName = 'user-' . $userId . '-' . Random::generate(10, '0-9a-z') . '.' . $extension;
		$directory = $this->directoryProvider->getRootDir() . '/www/' . $this->storageDirProvider->getUserImages();
		FileSystem::createDir($directory);
		$upload->move($directory . '/' . $fileName);

		$this->userRepository->updateImage($userId, $fileName);
		$this->flashMessage('Obrázok bol nahraný.', 'success');
		$this->redirect('this');
	}

	protected function validateAutosavePartialRequest(string $context, int $entityId): void
	{
		parent::validateAutosavePartialRequest($context, $entityId);

		$user = $this->userRepository->findById($entityId);
		if (!$user instanceof ActiveRow) {
			$this->sendAutosavePartialError(404, 'Používateľ neexistuje.');
			return;
		}

		$permission = (int) $user->{UserTableMap::COL_PERMISSION};
		if ($context === 'user.access') {
			if (!$this->canEditAccess($permission)) {
				$this->sendAutosavePartialError(403, 'Prístup zamietnutý.');
			}
			$request = $this->getHttpRequest();
			if ($request->getPost('__autosave_field') === 'permission') {
				$value = $request->getPost('__autosave_value');
				if (!is_string($value) || !isset($this->userRepository->findPermissionOptions($this->isAdmin())[$value])) {
					$this->sendAutosavePartialError(403, 'Prístup zamietnutý.');
				}
			}
			return;
		}

		if ($context === 'user.profile' && !$this->canEditUser($entityId, $permission)) {
			$this->sendAutosavePartialError(403, 'Prístup zamietnutý.');
		}
	}

	private function getEditableUserId(): int
	{
		if ($this->editableUserId !== null) {
			return $this->editableUserId;
		}

		if (!$this->getUser()->isLoggedIn() || !is_int($this->getUser()->getId())) {
			$this->error('Prístup zamietnutý', 403);
		}

		$id = $this->getParameter('id');
		$userId = is_numeric($id) ? (int) $id : $this->getUser()->getId();
		if ($this->userRepository->findById($userId) === null) {
			$this->error('Používateľ neexistuje.', 404);
		}

		return $this->editableUserId = $userId;
	}

	private function getCurrentUserRow(): ActiveRow
	{
		if ($this->currentUserRow !== null) {
			return $this->currentUserRow;
		}

		$user = $this->userRepository->findById($this->getEditableUserId());
		if (!$user instanceof ActiveRow) {
			$this->error('Používateľ neexistuje.', 404);
		}

		return $this->currentUserRow = $user;
	}

	private function canEditUser(int $targetUserId, int $targetPermission): bool
	{
		if ($this->getUser()->getId() === $targetUserId) {
			return true;
		}

		if (!$this->getUser()->isAllowed(Resource::USER_MANAGEMENT->value) || !$this->canManageOtherUsers()) {
			return false;
		}

		return $targetPermission !== UserRole::ADMIN->getPermissionId() || $this->isAdmin();
	}

	private function canEditAccess(int $targetPermission): bool
	{
		if (!$this->getUser()->isAllowed(Resource::USER_MANAGEMENT->value) || !$this->canManageOtherUsers()) {
			return false;
		}

		return $targetPermission !== UserRole::ADMIN->getPermissionId() || $this->isAdmin();
	}

	private function canManageOtherUsers(): bool
	{
		return $this->getUser()->isInRole(UserRole::CEO->value) || $this->isAdmin();
	}

	private function isAdmin(): bool
	{
		return $this->getUser()->isInRole(UserRole::ADMIN->value);
	}

	/**
	 * @return array{id:int,name:string,secondName:string,acronym:string,email:string,color:string,permission:int,active:int}
	 */
	private function createUserTemplateData(ActiveRow $user): array
	{
		return [
			'id' => (int) $user->{UserTableMap::COL_ID},
			'name' => (string) $user->{UserTableMap::COL_NAME},
			'secondName' => (string) $user->{UserTableMap::COL_SECOND_NAME},
			'acronym' => (string) $user->{UserTableMap::COL_ACRONYM},
			'email' => (string) $user->{UserTableMap::COL_EMAIL},
			'color' => (string) $user->{UserTableMap::COL_COLOR},
			'permission' => (int) $user->{UserTableMap::COL_PERMISSION},
			'active' => (int) $user->{UserTableMap::COL_ACTIVE},
		];
	}
}
