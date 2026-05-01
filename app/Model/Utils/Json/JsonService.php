<?php declare(strict_types = 1);

namespace App\Model\Utils\Json;

use Nette\Utils\Json;

class JsonService
{
	public function encodeToJson(mixed $value, bool $pretty = false, bool $asciiSafe = false, bool $htmlSafe = false, bool $forceObjects = false): string
	{
		return Json::encode($value, $pretty, $asciiSafe, $htmlSafe, $forceObjects);
	}

	public function decode(string $json, bool|int $forceArrays = false): mixed
	{
		return Json::decode($json, $forceArrays);
	}



	public function isValidJson(string $json): bool
	{
		json_decode($json);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	public function getLastJsonError(): ?string
	{
		$error = json_last_error_msg();
		return $error !== 'No error' ? $error : null;
	}
}
