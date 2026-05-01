<?php declare(strict_types=1);

namespace App\Model\Enum\UserRole;

enum UserRole: string
{
	case ADMIN = 'admin';
	case CEO = 'ceo';
	case DEALER = 'dealer';
	case DEALER_JUNIOR = 'dealerJunior';

	public static function fromPermissionId(int $permissionId): self
	{
		return match ($permissionId) {
			10 => self::ADMIN,
			5 => self::CEO,
			3 => self::DEALER,
			2 => self::DEALER_JUNIOR,
			default => throw new \ValueError("Unknown permission ID: $permissionId"),
		};
	}

	public function getPermissionId(): int
	{
		return match ($this) {
			self::ADMIN => 10,
			self::CEO => 5,
			self::DEALER => 3,
			self::DEALER_JUNIOR => 2,
		};
	}
}
