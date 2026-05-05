<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterEducation;

final readonly class BabysitterEducationForm
{
	public function __construct(
		public int $id,
		public int $education,
		public int $drivingLicence,
		public int $readyDrive,
		public int $languageSkills,
		public string $languageSkillsOther,
		public int $course,
		public string $courseDetail,
	) {
	}
}
