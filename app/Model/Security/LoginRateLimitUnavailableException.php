<?php declare(strict_types=1);

namespace App\Model\Security;

use RuntimeException;

/**
 * The login attempts could not be read, so nobody can tell whether the login is rate limited.
 * The caller decides what to do; it must not treat this as "not blocked".
 */
final class LoginRateLimitUnavailableException extends RuntimeException
{
}
