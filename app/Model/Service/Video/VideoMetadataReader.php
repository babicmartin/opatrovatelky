<?php declare(strict_types=1);

namespace App\Model\Service\Video;

final class VideoMetadataReader
{
	/**
	 * @return array{durationSeconds:int,mimeType:string}
	 */
	public function read(string $path): array
	{
		if (!is_file($path) || !is_readable($path)) {
			throw new \RuntimeException('Video súbor sa nepodarilo prečítať.');
		}

		$getId3 = new \getID3();
		$getId3->option_tag_id3v1 = false;
		$getId3->option_tag_id3v2 = false;
		$getId3->option_tags_process = false;
		$getId3->option_tags_html = false;

		/** @var array<string, mixed> $info */
		$info = $getId3->analyze($path);
		if (!empty($info['error'])) {
			throw new \RuntimeException('Video súbor sa nepodarilo analyzovať.');
		}

		if (empty($info['video']) || !is_array($info['video'])) {
			throw new \RuntimeException('Súbor neobsahuje video stopu.');
		}

		$duration = isset($info['playtime_seconds']) ? (float) $info['playtime_seconds'] : 0.0;
		if ($duration <= 0.0) {
			throw new \RuntimeException('Dĺžku videa sa nepodarilo zistiť.');
		}

		$mimeType = isset($info['mime_type']) ? (string) $info['mime_type'] : '';
		if ($mimeType === '') {
			throw new \RuntimeException('Typ videa sa nepodarilo zistiť.');
		}

		return [
			'durationSeconds' => (int) ceil($duration),
			'mimeType' => $mimeType,
		];
	}
}
