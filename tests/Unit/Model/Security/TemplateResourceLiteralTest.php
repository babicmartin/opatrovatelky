<?php declare(strict_types=1);

namespace Tests\Unit\Model\Security;

use App\Model\Enum\Acl\Resource;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\Support\PHPUnit\TestCase;

/**
 * Templates ask for permissions by a string literal. Nette\Security\Permission throws on a resource
 * it does not know, so a typo would bring the whole page down instead of hiding one button.
 */
final class TemplateResourceLiteralTest extends TestCase
{
	public function testEveryResourceLiteralInTemplatesIsAKnownResource(): void
	{
		$found = 0;
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			dirname(__DIR__, 4) . '/app/UI',
			FilesystemIterator::SKIP_DOTS,
		));

		foreach ($files as $file) {
			if (!$file instanceof SplFileInfo || $file->getExtension() !== 'latte') {
				continue;
			}

			$source = (string) file_get_contents($file->getPathname());
			preg_match_all('~isAllowed\(\s*([^)]*)\)~', $source, $calls);
			foreach ($calls[1] as $argument) {
				$found++;
				self::assertMatchesRegularExpression(
					'~^([\'"])[^\'"]+\1$~',
					trim($argument),
					$file->getPathname() . ': isAllowed() must be asked with a resource literal, got ' . $argument,
				);
				self::assertNotNull(
					Resource::tryFrom(trim($argument, " \t\n\r'\"")),
					$file->getPathname() . ': unknown resource ' . $argument,
				);
			}
		}

		self::assertGreaterThan(0, $found, 'No isAllowed() call was found; the scan is broken.');
	}
}
