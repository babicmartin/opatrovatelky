<?php declare(strict_types = 1);

namespace App\Model\Utils\String;

use InvalidArgumentException;
use Nette\Utils\Random;
use Nette\Utils\Strings;

class StringService
{

	public function removeAllWhiteSpaces(?string $string): ?string
	{
		if ($string === null) {
			return null;
		}
		return Strings::replace($string, '/\s+/', '');
	}

	public function startsWith(?string $haystack, string $needle): bool
	{
		if ($haystack === null || $haystack === '') {
			return false;
		}

		return str_starts_with($haystack, $needle);
	}

	public function endsWith(?string $haystack, string $needle): bool
	{
		if ($haystack === null || $haystack === '') {
			return false;
		}

		return str_ends_with($haystack, $needle);
	}


	public function substring(string $string, int $start, ?int $length = null): string
	{
		return Strings::substring($string, $start, $length);
	}

	public function after(string $haystack, string $needle, int $nth = 1): ?string
	{
		return Strings::after($haystack, $needle, $nth);
	}


	public function before(string $haystack, string $needle, int $nth = 1): ?string
	{
		return Strings::before($haystack, $needle, $nth);
	}

	public function findPrefix(string ...$strings): string
	{
		return Strings::findPrefix($strings);
	}


	public function padLeft(string $string, int $length, string $pad= ' '): string
	{
		/** @var non-empty-string $pad */
		return Strings::padLeft($string, $length, $pad);
	}

	public function padRight(string $string, int $length, string $pad= ' '): string
	{
		/** @var non-empty-string $pad */
		return Strings::padRight($string, $length, $pad);
	}

	public function reverse(string $string): string
	{
		return Strings::reverse($string);
	}

	public function getFirstLetter(string $string): string
	{
		return substr($string, 0, 1);
	}

	public function removeCharactersFromStart(?string $input, int $count): ?string
	{
		if ($input === null) {
			return null;
		}

		if ($count < 0) {
			throw new InvalidArgumentException('Count must be a non-negative integer.');
		}

		if ($count > strlen($input)) {
			return '';
		}

		return substr($input, $count);
	}

	public function removeCharactersFromEnd(?string $input, int $count): ?string
	{
		if ($input === null) {
			return null;
		}

		if ($count < 0) {
			throw new InvalidArgumentException('Count must be a non-negative integer.');
		}

		if ($count > strlen($input)) {
			return '';
		}

		return substr($input, 0, -$count);
	}


	public function length(string $input): int
	{
		return Strings::length($input);
	}


	/**
	 * @return array<int, string>
	 */
	public function explode(string $separator, string $input): array
	{
		if ($separator === '') {
			throw new InvalidArgumentException('Separator cannot be empty.');
		}
		return explode($separator, $input);
	}


	public function removeDiacritics(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::toAscii($input);
	}

	public function lower(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::lower($input);
	}

	public function upper(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::upper($input);
	}

	public function trim(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::trim($input);
	}

	public function webalize(?string $input, ?string $charList = null, bool $lower = true): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::webalize($input, $charList, $lower);
	}

	public function normalize(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::normalize($input);
	}

	public function contains(string $haystack, string $needle): bool
	{
		return Strings::contains($haystack, $needle);
	}

	public function replace(string $subject, string $pattern, string $replacement): string
	{
		// Add delimiters and escape special characters in the pattern
		$patternFormatted = '/' . preg_quote($pattern, '/') . '/';

		return Strings::replace($subject, $patternFormatted, $replacement);
	}

	public function firstUpper(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::firstUpper($input);
	}

	public function firstLower(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return Strings::firstLower($input);
	}

	public function removeHtmlTags(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return strip_tags($input);
	}

	public function htmlSpecialChars(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return htmlspecialchars($input,ENT_QUOTES, 'UTF-8');
		//return htmlspecialchars($input);
	}

	public function htmlSpecialCharsDecode(?string $input): ?string
	{
		if ($input === null) {
			return null;
		}

		return htmlspecialchars_decode($input);
	}


	public function randomString(int $length): string
	{
		/** @var int<1, max> $length */
		return Random::generate($length);
	}

	public function pdfTextNormalize(?string $text): ?string
	{
		if ($text === null) {
			return null;
		}

		$string = htmlentities($text, ENT_QUOTES, 'utf-8');
		$content = str_replace("&nbsp;", " ", $string);
		return html_entity_decode($content);
	}


}
