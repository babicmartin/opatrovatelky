<?php declare(strict_types=1);

namespace App\UI\Admin\Response;

use Nette\Application\BadRequestException;
use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

use function strlen;

final class InlineVideoResponse implements Response
{
	public function __construct(
		private readonly string $file,
		private readonly string $name,
		private readonly string $contentType,
	) {
		if (!is_file($this->file) || !is_readable($this->file)) {
			throw new BadRequestException("File '{$this->file}' doesn't exist or is not readable.");
		}
	}

	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$httpResponse->setContentType($this->contentType);
		$httpResponse->setHeader('X-Content-Type-Options', 'nosniff');
		$httpResponse->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
		$httpResponse->setHeader(
			'Content-Disposition',
			'inline; filename="' . str_replace('"', '', $this->name) . '"; filename*=utf-8\'\'' . rawurlencode($this->name),
		);

		$fileSize = filesize($this->file);
		if ($fileSize === false) {
			throw new BadRequestException("Cannot read file size: '{$this->file}'.");
		}

		$length = $fileSize;
		$handle = fopen($this->file, 'rb');
		if (!$handle) {
			throw new BadRequestException("Cannot open file: '{$this->file}'.");
		}

		$httpResponse->setHeader('Accept-Ranges', 'bytes');
		$range = (string) $httpRequest->getHeader('Range');
		if (preg_match('#^bytes=(\d*)-(\d*)$#D', $range, $matches)) {
			if ($matches[1] === '' && $matches[2] === '') {
				$httpResponse->setCode(416);
				fclose($handle);
				return;
			}

			$start = $matches[1] === '' ? 0 : (int) $matches[1];
			$end = $matches[2] === '' ? $fileSize - 1 : (int) $matches[2];

			if ($matches[1] === '') {
				$start = max(0, $fileSize - $end);
				$end = $fileSize - 1;
			} elseif ($end > $fileSize - 1) {
				$end = $fileSize - 1;
			}

			if ($end < $start) {
				$httpResponse->setCode(416);
				fclose($handle);
				return;
			}

			$httpResponse->setCode(206);
			$httpResponse->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $fileSize);
			$length = $end - $start + 1;
			fseek($handle, $start);
		} else {
			$httpResponse->setHeader('Content-Range', 'bytes 0-' . ($fileSize - 1) . '/' . $fileSize);
		}

		$httpResponse->setHeader('Content-Length', (string) $length);
		while (!feof($handle) && $length > 0) {
			$chunk = fread($handle, min(4_000_000, $length));
			if ($chunk === false) {
				break;
			}

			echo $chunk;
			$length -= strlen($chunk);
		}

		fclose($handle);
	}
}
