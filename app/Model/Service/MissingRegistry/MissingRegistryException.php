<?php declare(strict_types=1);

namespace App\Model\Service\MissingRegistry;

/**
 * A registry operation that was refused. The code is the HTTP status the caller should answer with
 * and the message is safe to show to the user.
 */
final class MissingRegistryException extends \RuntimeException
{
	public const int NOT_FOUND = 404;
	public const int ACCESS_DENIED = 403;
	public const int INVALID = 422;

	public static function notFound(): self
	{
		return new self('Evidencia neexistuje.', self::NOT_FOUND);
	}

	public static function accessDenied(): self
	{
		return new self('Na úpravu tejto evidencie nemáte oprávnenie.', self::ACCESS_DENIED);
	}

	public static function invalid(string $message): self
	{
		return new self($message, self::INVALID);
	}

	public function isInvalidInput(): bool
	{
		return $this->getCode() === self::INVALID;
	}
}
