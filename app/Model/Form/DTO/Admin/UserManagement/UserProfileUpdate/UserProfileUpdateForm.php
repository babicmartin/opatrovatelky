<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate;

final readonly class UserProfileUpdateForm
{
	public function __construct(
		private string $name,
		private string $secondName,
		private string $acronym,
		private string $email,
		private string $color,
	) {
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getSecondName(): string
	{
		return $this->secondName;
	}

	public function getAcronym(): string
	{
		return $this->acronym;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getColor(): string
	{
		return $this->color;
	}
}
