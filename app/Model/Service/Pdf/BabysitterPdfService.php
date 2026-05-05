<?php declare(strict_types=1);

namespace App\Model\Service\Pdf;

use App\Model\DataProvider\Directory\DirectoryProvider;
use App\Model\DataProvider\Directory\StorageDirProvider;
use App\Model\Repository\BabysitterPdfRepository;
use DateTimeImmutable;
use Latte\Engine;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Nette\Utils\FileSystem;
use RuntimeException;

final class BabysitterPdfService
{
	private const string DEFAULT_FONT = 'Bahnschrift Condensed';

	private const string FOOTER_HTML =
		'<div style="text-align:center; background:#414042; width:100%; height:32px; line-height:32px; color:#fff; font-size:14px; font-style:normal">'
		. 'Fair – Schnell – Kompetent - Professionell &#124; Betreuung von zu Hause &#124; '
		. '<img src="{IMAGES_DIR}/mail.png" style="height:28px; line-height:33px; vertical-align:middle"/> '
		. '<span style="color:#fff">office@altenpflege-4ms.eu</span>'
		. '</div>';

	public function __construct(
		private readonly BabysitterPdfRepository $babysitterPdfRepository,
		private readonly DirectoryProvider $directoryProvider,
		private readonly StorageDirProvider $storageDirProvider,
	) {
	}

	public function generate(int $babysitterId): void
	{
		$data = $this->babysitterPdfRepository->findPdfData($babysitterId);
		if ($data === null) {
			throw new RuntimeException(sprintf('Babysitter %d not found.', $babysitterId));
		}

		$wwwDir = $this->getWwwDir();
		$imagesDir = $wwwDir . '/img/profil';
		$profileImageHtml = $this->buildProfileImageHtml((string) $data->babysitter['image'], $wwwDir);

		$html = $this->renderHtml($data, $wwwDir, $imagesDir, $profileImageHtml);
		$css = $this->loadCss($wwwDir);
		$footer = str_replace('{IMAGES_DIR}', $imagesDir, self::FOOTER_HTML);

		$tempDir = $this->directoryProvider->getTempDir() . '/mpdf';
		FileSystem::createDir($tempDir);

		$mpdf = new Mpdf([
			'default_font' => self::DEFAULT_FONT,
			'tempDir' => $tempDir,
		]);
		$mpdf->AddPageByArray([
			'margin-left' => 0,
			'margin-right' => 0,
			'margin-top' => 0,
			'margin-bottom' => 0,
			'margin-footer' => 0,
		]);
		$mpdf->SetHtmlFooter($footer);
		$mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($html);

		$targetDir = $this->getTargetDir();
		FileSystem::createDir($targetDir);
		$mpdf->Output($targetDir . '/' . $babysitterId . '.pdf', Destination::FILE);
	}

	public function getAbsolutePath(int $babysitterId): string
	{
		return $this->getTargetDir() . '/' . $babysitterId . '.pdf';
	}

	public function exists(int $babysitterId): bool
	{
		return is_file($this->getAbsolutePath($babysitterId));
	}

	public function getGeneratedAt(int $babysitterId): ?DateTimeImmutable
	{
		$path = $this->getAbsolutePath($babysitterId);
		if (!is_file($path)) {
			return null;
		}

		$mtime = filemtime($path);
		if ($mtime === false) {
			return null;
		}

		return (new DateTimeImmutable())->setTimestamp($mtime);
	}

	private function renderHtml(
		\App\Model\DTO\Pdf\BabysitterPdfData $data,
		string $wwwDir,
		string $imagesDir,
		string $profileImageHtml,
	): string {
		$engine = new Engine();
		$engine->setTempDirectory($this->directoryProvider->getTempDir() . '/latte-pdf');
		$engine->setAutoRefresh(true);

		return $engine->renderToString(__DIR__ . '/templates/babysitter.pdf.latte', [
			'data' => $data,
			'babysitter' => $data->babysitter,
			'translation' => $data->translations,
			'wwwDir' => $wwwDir,
			'imagesDir' => $imagesDir,
			'profileImageHtml' => $profileImageHtml,
		]);
	}

	private function loadCss(string $wwwDir): string
	{
		$cssPath = $wwwDir . '/assets/css/pdf.css';
		$css = @file_get_contents($cssPath);
		if ($css === false) {
			throw new RuntimeException(sprintf('Cannot read PDF stylesheet at %s', $cssPath));
		}

		return str_replace('web/img/', $wwwDir . '/img/', $css);
	}

	private function buildProfileImageHtml(string $image, string $wwwDir): string
	{
		$baseStyle = 'max-width:180px; max-height:180px; margin-left:auto; margin-right:auto; display:block;';
		$userImagesDir = $wwwDir . '/' . $this->storageDirProvider->getUserImages();
		$emptyImage = $wwwDir . '/' . $this->storageDirProvider->getUserImagesEmpty();
		$src = $image !== '' && is_file($userImagesDir . '/' . $image)
			? $userImagesDir . '/' . $image
			: $emptyImage;

		return '<img style="' . $baseStyle . '" src="' . $src . '">';
	}

	private function getTargetDir(): string
	{
		return $this->normalize($this->directoryProvider->getRootDir() . '/' . $this->storageDirProvider->getBabysitterPdf());
	}

	private function getWwwDir(): string
	{
		return $this->normalize($this->directoryProvider->getRootDir() . '/www');
	}

	private function normalize(string $path): string
	{
		return str_replace('\\', '/', $path);
	}
}
