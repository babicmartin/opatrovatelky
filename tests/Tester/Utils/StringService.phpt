<?php declare(strict_types=1);

use App\Model\Utils\String\StringService;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test('StringService removes whitespace and preserves null', function (): void {
	$service = new StringService();

	Assert::null($service->removeAllWhiteSpaces(null));
	Assert::same('abc', $service->removeAllWhiteSpaces(" a \n b\tc "));
});

test('StringService webalizes Slovak text', function (): void {
	$service = new StringService();

	Assert::same('opatrovatelka', $service->webalize('Opatrovateľka'));
});
