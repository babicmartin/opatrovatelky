<?php declare(strict_types=1);

namespace Tests\Support\Fixture;

use App\Model\Repository\BabysitterVideoRepository;
use App\Model\Table\FileTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use Tests\Support\Database\TestDatabase;

final readonly class BabysitterPrivateFiles
{
	public int $ownerId;
	public int $documentId;
	public int $videoId;
	public string $documentPath;
	public string $videoPath;
	public string $documentContents;
	public string $videoContents;
	public string $notice;
	public string $videoName;
	public string $surname;


	public function __construct(
		public string $label,
		string $documentRoot,
		string $videoRoot,
		string $sampleVideo,
		int $userId,
		BabysitterVideoRepository $videos,
	) {
		$this->surname = 'Profile-' . $label;
		$this->ownerId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_ID => random_int(1_000_000, 1_000_000_000),
			OpatrovatelkaTableMap::COL_SURNAME => $this->surname,
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-' . $label,
		]);
		$dir = 'babysitters/' . $this->ownerId;
		$this->notice = 'Private note ' . $label;
		$this->documentPath = $documentRoot . '/' . $dir . '/document-' . $label . '.pdf';
		$this->documentContents = "%PDF-1.4\n%Document content " . $label . "\n%%EOF\n";
		$this->documentId = TestDatabase::createFile([
			FileTableMap::COL_DIR => $dir,
			FileTableMap::COL_NAME => basename($this->documentPath),
			FileTableMap::COL_TYPE => 'pdf',
			FileTableMap::COL_NOTICE => $this->notice,
			FileTableMap::COL_USER => $userId,
		]);

		$this->videoName = 'recording-' . $label . '.mp4';
		$this->videoPath = $videoRoot . '/' . $dir . '/stored-' . $label . '.mp4';
		$marker = 'Video content ' . $label;
		$this->videoContents = $sampleVideo . pack('N', 8 + strlen($marker)) . 'free' . $marker;
		$this->videoId = $videos->insertVideo([
			'babysitterId' => $this->ownerId,
			'originalName' => $this->videoName,
			'storedName' => basename($this->videoPath),
			'extension' => 'mp4',
			'mimeType' => 'video/mp4',
			'sizeBytes' => strlen($this->videoContents),
			'durationSeconds' => 1,
			'checksumSha256' => hash('sha256', $this->videoContents),
			'uploadedByUserId' => $userId,
		]);
	}


	public function getPagePath(): string
	{
		return '/babysitter/update/' . $this->ownerId;
	}
}
