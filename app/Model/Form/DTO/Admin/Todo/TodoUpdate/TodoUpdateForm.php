<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Todo\TodoUpdate;

final readonly class TodoUpdateForm
{
	public function __construct(
		public int $id,
		public int $familyId,
		public int $babysitterId,
		public int $todoFromUser,
		public int $todoToUser1,
		public int $todoToUser2,
		public ?\DateTimeImmutable $todoCreated,
		public ?\DateTimeImmutable $todoDeadline,
		public int $status,
		public string $title,
		public string $description,
		public string $answer,
	) {
	}
}
