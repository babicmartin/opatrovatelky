<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterVideos;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\BabysitterVideoRepository;
use App\Model\Repository\ChangeLogRepository;
use App\Model\Service\Video\VideoMetadataReader;
use App\Model\Table\BabysitterVideoTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\UI\Admin\Response\InlineVideoResponse;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Helpers;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;

class BabysitterVideosControl extends Control
{
	private const string DIR = 'babysitters';
	private const int MAX_FILE_SIZE = 209_715_200;
	private const int MAX_DURATION_SECONDS = 600;
	private const array ALLOWED_EXTENSIONS = ['mp4', 'webm'];
	private const array ALLOWED_MIME_TYPES = ['video/mp4', 'video/webm'];

	private int $babysitterId = 0;

	public function __construct(
		private readonly BabysitterVideoRepository $videoRepository,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
		private readonly User $user,
		private readonly VideoMetadataReader $videoMetadataReader,
		private readonly LoggerInterface $logger,
		private readonly IRequest $httpRequest,
		private readonly ChangeLogRepository $changeLogRepository,
	) {
	}

	public function setContext(int $babysitterId): static
	{
		$this->babysitterId = $babysitterId;

		return $this;
	}

	public function render(): void
	{
		$this->assertCanManage();

		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/BabysitterVideosControl.latte');
		$template->videos = $this->videoRepository->findForBabysitter($this->babysitterId);
		$template->canDeleteVideos = $this->canDeleteVideos();
		$template->maxFileSize = $this->getEffectiveMaxFileSize();
		$template->maxFileSizeLabel = $this->formatBytes($this->getEffectiveMaxFileSize());
		$template->appMaxFileSizeLabel = $this->formatBytes(self::MAX_FILE_SIZE);
		$template->serverLimitIsLower = $this->getEffectiveMaxFileSize() < self::MAX_FILE_SIZE;
		$template->serverUploadLimitLabel = $this->formatBytes($this->getServerUploadLimit());
		$template->maxDurationLabel = $this->formatDuration(self::MAX_DURATION_SECONDS);
		$template->allowedExtensions = self::ALLOWED_EXTENSIONS;
		$template->allowedExtensionsLabel = implode(', ', self::ALLOWED_EXTENSIONS);
		$template->render();
	}

	public function handlePlay(int $id): void
	{
		$this->assertCanManage();

		$video = $this->findVideoOrError($id);
		$path = $this->getVideoPath($video);
		if (!is_file($path)) {
			$this->getPresenter()->error('Video súbor neexistuje.', 404);
		}

		$this->getPresenter()->sendResponse(new InlineVideoResponse($path, (string) $video['originalName'], (string) $video['mimeType']));
	}

	public function handleDownload(int $id): void
	{
		$this->assertCanManage();

		$video = $this->findVideoOrError($id);
		$path = $this->getVideoPath($video);
		if (!is_file($path)) {
			$this->getPresenter()->error('Video súbor neexistuje.', 404);
		}

		$this->getPresenter()->sendResponse(new FileResponse($path, (string) $video['originalName'], (string) $video['mimeType']));
	}

	public function handleDelete(int $id): void
	{
		$this->assertCanDelete();

		$video = $this->findVideoOrError($id);
		$path = $this->getVideoPath($video);
		$this->videoRepository->softDelete($id, (int) $this->user->getId());
		$this->logVideoChange('deleted', $video);
		if (is_file($path)) {
			FileSystem::delete($path);
		}

		$this->redirect('this');
	}

	protected function createComponentUploadForm(): Form
	{
		$this->assertCanManage();

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'js-babysitter-video-upload-form')
			->setAttribute('enctype', 'multipart/form-data');
		$form->addUpload('video', 'Video')
			->setRequired('Vyberte video.')
			->setHtmlAttribute('class', 'form-control js-babysitter-video-input')
			->setHtmlAttribute('accept', '.mp4,.webm,video/mp4,video/webm')
			->setHtmlAttribute('data-max-size', (string) $this->getEffectiveMaxFileSize())
			->setHtmlAttribute('data-allowed-extensions', implode(',', self::ALLOWED_EXTENSIONS));
		$form->addSubmit('upload', 'Nahrať video')
			->setHtmlAttribute('class', 'btn btn-success btn-sm js-babysitter-video-submit');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$this->assertCanManage();

			$upload = $values->video;
			if (!$upload instanceof FileUpload) {
				$this->failUpload($form, 'Video sa nepodarilo nahrať.', 'missing_upload_value');
				return;
			}

			if (!$upload->hasFile()) {
				$this->failUpload($form, 'Vyberte video.', 'no_file', $upload);
				return;
			}

			if (!$upload->isOk()) {
				$this->failUpload($form, $this->getUploadErrorMessage($upload), 'php_upload_error', $upload);
				return;
			}

			$extension = strtolower(pathinfo($upload->getSanitizedName(), PATHINFO_EXTENSION));
			if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
				$this->failUpload($form, 'Povolené sú iba videá typu: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.', 'invalid_extension', $upload, [
					'extension' => $extension,
				]);
				return;
			}

			if ($upload->getSize() > self::MAX_FILE_SIZE) {
				$this->failUpload($form, 'Maximálna veľkosť videa je ' . $this->formatBytes(self::MAX_FILE_SIZE) . '.', 'app_file_size_limit', $upload);
				return;
			}

			$contentType = (string) $upload->getContentType();
			if (!in_array($contentType, self::ALLOWED_MIME_TYPES, true)) {
				$this->failUpload($form, 'Nepovolený typ video súboru.', 'invalid_detected_mime_type', $upload, [
					'detectedContentType' => $contentType,
				]);
				return;
			}

			try {
				$metadata = $this->videoMetadataReader->read($upload->getTemporaryFile());
			} catch (\RuntimeException $e) {
				$this->failUpload($form, $e->getMessage(), 'metadata_read_failed', $upload, [
					'exception' => $e->getMessage(),
				]);
				return;
			}

			if (!in_array($metadata['mimeType'], self::ALLOWED_MIME_TYPES, true)) {
				$this->failUpload($form, 'Nepovolený typ video súboru.', 'invalid_metadata_mime_type', $upload, [
					'metadataMimeType' => $metadata['mimeType'],
				]);
				return;
			}

			if ($metadata['durationSeconds'] > self::MAX_DURATION_SECONDS) {
				$this->failUpload($form, 'Maximálna dĺžka videa je ' . $this->formatDuration(self::MAX_DURATION_SECONDS) . '.', 'duration_limit', $upload, [
					'durationSeconds' => $metadata['durationSeconds'],
				]);
				return;
			}

			$storedName = 'babysitter-' . $this->babysitterId . '-' . Random::generate(32, '0-9a-z') . '.' . $extension;
			$targetDir = $this->getVideoDir();
			$targetPath = $targetDir . '/' . $storedName;
			$originalName = Strings::truncate($upload->getUntrustedName(), 255, '');

			FileSystem::createDir($targetDir);
			$checksum = hash_file('sha256', $upload->getTemporaryFile());
			if ($checksum === false) {
				$this->failUpload($form, 'Kontrolný súčet videa sa nepodarilo vytvoriť.', 'checksum_failed', $upload);
				return;
			}

			try {
				$upload->move($targetPath);
				$videoId = $this->videoRepository->insertVideo([
					'babysitterId' => $this->babysitterId,
					'originalName' => $originalName !== '' ? $originalName : $storedName,
					'storedName' => $storedName,
					'extension' => $extension,
					'mimeType' => $metadata['mimeType'],
					'sizeBytes' => $upload->getSize(),
					'durationSeconds' => $metadata['durationSeconds'],
					'checksumSha256' => $checksum,
					'uploadedByUserId' => (int) $this->user->getId(),
				]);
				$this->logVideoChange('uploaded', [
					'id' => $videoId,
					'babysitterId' => $this->babysitterId,
					'originalName' => $originalName !== '' ? $originalName : $storedName,
					'storedName' => $storedName,
					'extension' => $extension,
					'mimeType' => $metadata['mimeType'],
					'sizeBytes' => $upload->getSize(),
					'durationSeconds' => $metadata['durationSeconds'],
					'checksumSha256' => $checksum,
				]);
			} catch (\Throwable $e) {
				if (is_file($targetPath)) {
					FileSystem::delete($targetPath);
				}

				$this->logUploadFailure('storage_failed', 'Video sa nepodarilo uložiť.', $upload, [
					'exceptionClass' => $e::class,
					'exceptionMessage' => $e->getMessage(),
				], 'error');
				if ($this->getPresenter()->isAjax()) {
					$this->sendUploadError('Video sa nepodarilo uložiť. Detail je v logu aplikácie.', 500);
				}

				throw $e;
			}

			if ($this->getPresenter()->isAjax()) {
				$this->getPresenter()->sendJson(['success' => true]);
			}

			$this->flashMessage('Video bolo nahraté.', 'success');
			$this->redirect('this');
		};

		$form->onError[] = function (Form $form): void {
			$upload = $this->getUploadFromForm($form);
			$message = $this->getPostLimitErrorMessage()
				?? ($upload instanceof FileUpload ? $this->getUploadErrorMessage($upload) : null)
				?? ($form->getErrors()[0] ?? 'Video sa nepodarilo nahrať.');
			$this->logUploadFailure('form_validation_failed', $message, $upload, [
				'formErrors' => $form->getErrors(),
			]);

			if ($this->getPresenter()->isAjax()) {
				$this->sendUploadError($message);
			}
		};

		return $form;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function findVideoOrError(int $id): array
	{
		$video = $this->videoRepository->findForBabysitterById($this->babysitterId, $id);
		if ($video === null) {
			$this->getPresenter()->error('Video neexistuje.', 404);
		}

		return $video;
	}

	private function assertCanManage(): void
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}
	}

	private function assertCanDelete(): void
	{
		$this->assertCanManage();
		if (!$this->canDeleteVideos()) {
			$this->getPresenter()->error('Prístup zamietnutý', 403);
		}
	}

	private function canDeleteVideos(): bool
	{
		return $this->user->isInRole(UserRole::CEO->value) || $this->user->isInRole(UserRole::ADMIN->value);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function failUpload(Form $form, string $message, string $reason, ?FileUpload $upload = null, array $context = []): void
	{
		$this->logUploadFailure($reason, $message, $upload, $context);
		$form->addError($message);
		if ($this->getPresenter()->isAjax()) {
			$this->sendUploadError($message);
		}
	}

	private function sendUploadError(string $message, int $statusCode = 400): void
	{
		$this->getPresenter()->getHttpResponse()->setCode($statusCode);
		$this->getPresenter()->sendJson([
			'success' => false,
			'message' => $message,
		]);
	}

	private function getUploadFromForm(Form $form): ?FileUpload
	{
		$component = $form->getComponent('video', false);
		if (!$component instanceof UploadControl) {
			return null;
		}

		$value = $component->getValue();
		return $value instanceof FileUpload ? $value : null;
	}

	private function getUploadErrorMessage(FileUpload $upload): string
	{
		return match ($upload->getError()) {
			UPLOAD_ERR_OK => $this->getPostLimitErrorMessage() ?? 'Video sa nepodarilo nahrať.',
			UPLOAD_ERR_INI_SIZE => 'Video je väčšie ako serverový upload limit (' . ini_get('upload_max_filesize') . ').',
			UPLOAD_ERR_FORM_SIZE => 'Video je väčšie ako povolený limit formulára.',
			UPLOAD_ERR_PARTIAL => 'Video bolo nahraté iba čiastočne. Skúste upload zopakovať.',
			UPLOAD_ERR_NO_FILE => 'Vyberte video.',
			UPLOAD_ERR_NO_TMP_DIR => 'Server nemá dostupný dočasný priečinok pre upload.',
			UPLOAD_ERR_CANT_WRITE => 'Video sa nepodarilo zapísať na server.',
			UPLOAD_ERR_EXTENSION => 'Upload videa zastavilo PHP rozšírenie.',
			default => 'Video sa nepodarilo nahrať. Upload error code: ' . $upload->getError() . '.',
		};
	}

	private function getPostLimitErrorMessage(): ?string
	{
		$contentLength = (int) ($this->httpRequest->getHeader('Content-Length') ?? 0);
		$postMaxSize = Helpers::iniGetSize('post_max_size');
		if ($postMaxSize > 0 && $contentLength > $postMaxSize) {
			return 'Video je väčšie ako serverový POST limit (' . ini_get('post_max_size') . ').';
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function logUploadFailure(string $reason, string $message, ?FileUpload $upload = null, array $context = [], string $level = 'warning'): void
	{
		$logContext = [
			'reason' => $reason,
			'message' => $message,
			'babysitterId' => $this->babysitterId,
			'userId' => $this->user->getId(),
			'remoteAddress' => $this->httpRequest->getRemoteAddress(),
			'contentLength' => $this->httpRequest->getHeader('Content-Length'),
			'phpUploadMaxFilesize' => ini_get('upload_max_filesize'),
			'phpPostMaxSize' => ini_get('post_max_size'),
			'appMaxFileSizeBytes' => self::MAX_FILE_SIZE,
			'effectiveMaxFileSizeBytes' => $this->getEffectiveMaxFileSize(),
			'appMaxDurationSeconds' => self::MAX_DURATION_SECONDS,
		];

		if ($upload !== null) {
			$logContext += [
				'uploadErrorCode' => $upload->getError(),
				'uploadErrorName' => $this->getUploadErrorName($upload->getError()),
				'clientFilename' => $upload->getSanitizedName(),
				'clientSizeBytes' => $upload->getSize(),
				'clientContentType' => $upload->isOk() ? $upload->getContentType() : null,
			];
		}

		$this->logger->log($level, 'Babysitter video upload failed.', $logContext + $context);
	}

	private function getUploadErrorName(int $errorCode): string
	{
		return match ($errorCode) {
			UPLOAD_ERR_OK => 'UPLOAD_ERR_OK',
			UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
			UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
			UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
			UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
			UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
			UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
			UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION',
			default => 'UPLOAD_ERR_UNKNOWN',
		};
	}

	/**
	 * @param array<string, mixed> $video
	 */
	private function logVideoChange(string $action, array $video): void
	{
		$videoId = (int) $video['id'];
		$originalName = (string) $video['originalName'];

		try {
			$this->changeLogRepository->logChange([
				'context' => 'babysitter.video',
				'entityTable' => OpatrovatelkaTableMap::TABLE_NAME,
				'entityId' => $this->babysitterId,
				'fieldName' => 'video',
				'fieldLabel' => 'Video',
				'columnName' => null,
				'valueType' => 'file',
				'oldValueId' => $action === 'deleted' ? (string) $videoId : null,
				'oldValueLabel' => $action === 'deleted' ? $originalName : null,
				'newValueId' => $action === 'uploaded' ? (string) $videoId : null,
				'newValueLabel' => $action === 'uploaded' ? $originalName : null,
				'userId' => $this->user->isLoggedIn() && is_int($this->user->getId()) ? (int) $this->user->getId() : null,
				'metadata' => [
					'action' => $action,
					'video_id' => $videoId,
					'video_table' => BabysitterVideoTableMap::TABLE_NAME,
					'original_name' => $originalName,
					'stored_name' => (string) ($video['storedName'] ?? ''),
					'extension' => (string) ($video['extension'] ?? ''),
					'mime_type' => (string) ($video['mimeType'] ?? ''),
					'size_bytes' => (int) ($video['sizeBytes'] ?? 0),
					'duration_seconds' => $video['durationSeconds'] ?? null,
					'checksum_sha256' => (string) ($video['checksumSha256'] ?? ''),
				],
			]);
		} catch (\Throwable $e) {
			$this->logger->error('Babysitter video change log failed.', [
				'action' => $action,
				'babysitterId' => $this->babysitterId,
				'videoId' => $videoId,
				'exceptionClass' => $e::class,
				'exceptionMessage' => $e->getMessage(),
			]);
		}
	}

	private function getEffectiveMaxFileSize(): int
	{
		$serverUploadLimit = $this->getServerUploadLimit();
		if ($serverUploadLimit <= 0) {
			return self::MAX_FILE_SIZE;
		}

		return min(self::MAX_FILE_SIZE, $serverUploadLimit);
	}

	private function getServerUploadLimit(): int
	{
		$uploadMaxSize = Helpers::iniGetSize('upload_max_filesize');
		$postMaxSize = Helpers::iniGetSize('post_max_size');
		if ($uploadMaxSize <= 0) {
			return $postMaxSize;
		}
		if ($postMaxSize <= 0) {
			return $uploadMaxSize;
		}

		return min($uploadMaxSize, $postMaxSize);
	}

	private function getVideoDir(): string
	{
		return $this->directoryProvider->getRootDir() . '/' . $this->storageDirProvider->getPrivateVideos() . '/' . self::DIR . '/' . $this->babysitterId;
	}

	/**
	 * @param array<string, mixed> $video
	 */
	private function getVideoPath(array $video): string
	{
		return $this->getVideoDir() . '/' . basename((string) $video['storedName']);
	}

	private function formatBytes(int $bytes): string
	{
		return $bytes >= 1_048_576
			? number_format($bytes / 1_048_576, 0, ',', ' ') . ' MB'
			: number_format($bytes / 1024, 0, ',', ' ') . ' kB';
	}

	private function formatDuration(int $seconds): string
	{
		$minutes = intdiv($seconds, 60);
		$remainingSeconds = $seconds % 60;

		return sprintf('%d:%02d', $minutes, $remainingSeconds);
	}
}
