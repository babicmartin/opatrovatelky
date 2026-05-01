<?php declare(strict_types = 1);

namespace App\Model\Utils\Hash;

class HashService
{
	public function md5File(string $filename): string
	{
		$result = md5_file($filename);
		return $result === false ? '' : $result;
	}
}
