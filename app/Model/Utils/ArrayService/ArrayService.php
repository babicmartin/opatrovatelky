<?php declare(strict_types = 1);

namespace App\Model\Utils\ArrayService;

use Nette\Utils\Arrays;

class ArrayService
{

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function paginateArray(array $inputArray, int $countItems, int $page): array {
		$offset = ($page - 1) * $countItems;

		return array_slice($inputArray, $offset, $countItems);
	}

	/**
	 * @template TKey of array-key
	 * @template TValue
	 * @param TValue $value
	 * @param array<TKey, TValue> $inputArray
	 * @return TKey|false
	 */
	public function getKeyByValue(mixed $value, array $inputArray): string|int|false
	{
		return array_search($value, $inputArray, true);
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function arrayReverse(array $inputArray): array
	{
		return array_reverse($inputArray);
	}

	/**
	 * @param array-key $key
	 * @param array<array-key, mixed> $inputArray
	 */
	public function arrayKeyExists(string|int $key, array $inputArray): bool
	{
		return array_key_exists($key, $inputArray);
	}

	/**
	 * @template T
	 * @param array<T> $array1
	 * @param array<T> $array2
	 * @return array<T>
	 */
	public function addArrayToArray(array &$array1, array $array2): array
	{
		foreach ($array2 as $element) {
			$array1[] = $element;
		}

		return $array1;
	}

	/**
	 * @template T
	 * @param array<T> $array1
	 * @param array<T> $array2
	 * @return array<T>
	 */
	public function addArrayToArrayUniqueValues(array &$array1, array $array2): array
	{
		// Merge the two arrays
		$mergedArray = array_merge($array1, $array2);

		// Remove duplicate values
		return array_unique($mergedArray, SORT_REGULAR);

	}

	/**
	 * @param array<string|int|float|bool|null> $inputArray
	 */
	public function naturalOrderByValueAscending(array &$inputArray): void
	{
		natsort($inputArray);
	}

	/**
	 * @param array<mixed> $inputArray
	 */
	public function sortByValueAscending(array &$inputArray): void
	{
		sort($inputArray);
	}

	/**
	 * @param array<mixed> $inputArray
	 */
	public function sortByValueDescending(array &$inputArray): void
	{
		rsort($inputArray);
	}

	/**
	 * @template TKey of array-key
	 * @template TValue
	 * @param TKey $key
	 * @param array<TKey, TValue> $array
	 * @return array<TKey, TValue>
	 */
	public function removeItemByKey(string|int $key, array $array): array
	{
		unset($array[$key]);

		return $array;
	}

	/**
	 * @template TKey of array-key
	 * @template TValue
	 * @param TKey $key
	 * @param array<TKey, TValue> $array
	 */
	public function removeItemByKeyReference(string|int $key, array &$array): void
	{
		unset($array[$key]);
	}

	/**
	 * @param array-key $key
	 * @param array<array-key, mixed> $array
	 */
	public function hasKey(string|int $key, array $array): bool
	{
		return array_key_exists($key, $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function countElements(array $array): int
	{
		return count($array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function hasElements(array $array): bool
	{
		return $this->countElements($array) > 0;
	}

	/**
	 * @param mixed $value
	 * @param array<mixed> $array
	 */
	public function inArray(mixed $value, array $array): bool
	{
		return Arrays::contains($array, $value);
	}

	/**
	 * @param array<string|int|float|bool|null> $array
	 */
	public function implode(string $separator, array $array): string
	{
		return implode($separator, $array);
	}

	/**
	 * @param array<int|float> $array
	 */
	public function getMaxValue(array $array, bool $returnFloat = true): int|float
	{
		$returnedNumber = $returnFloat === true ? 0.0 : 0;

		return !empty($array) ? max($array) : $returnedNumber;
	}

	/**
	 * @template TKey of array-key
	 * @param array<TKey, mixed> $array
	 * @return TKey|null
	 */
	public function getFirstKey(array $array): string|int|null
	{
		return array_key_first($array);
	}

	/**
	 * @template T
	 * @param array<T> $array
	 * @return T|null
	 */
	public function getFirstElement(array $array): mixed
	{
		return Arrays::first($array);
	}

	/**
	 * @template T
	 * @param array<T> $array
	 * @return array<T>
	 */
	public function removeFirstElement(array $array): array
	{
		array_shift($array);

		return $array;
	}

	/**
	 * @template T
	 * @param array<T> $array
	 * @return array<T>
	 */
	public function removeLastElement(array $array): array
	{
		array_pop($array);

		return $array;
	}

	/**
	 * @param array<mixed> ...$arrays
	 * @return array<mixed>
	 */
	public function joinArrays(array ...$arrays): array
	{
		return array_merge(...$arrays);
	}

	/**
	 * @param array<mixed> ...$arrays
	 * @return array<mixed>
	 */
	public function joinArraysWithKeys(array ...$arrays): array
	{
		$result = [];
		foreach ($arrays as $array) {
			$result += $array;  // Use the `+` operator to preserve keys
		}
		return $result;
	}

	/**
	 * @param array<mixed> ...$arrays
	 * @return array<mixed>
	 */
	public function joinArraysUniqueValues(array ...$arrays): array
	{

		// Merge all arrays into one
		$merged = array_merge(...$arrays);

		// Use serialization to ensure object uniqueness
		$unique = array_unique(array_map('serialize', $merged));

		// Deserialize the unique elements back into objects
		return array_map('unserialize', $unique);
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array{array<T>, array<T>}
	 */
	public function splitArrayAtIndex(array $inputArray, int $index): array
	{
		$firstPart = array_slice($inputArray, 0, $index);
		$secondPart = array_slice($inputArray, $index);

		return [$firstPart, $secondPart];
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function reverseArray(array $inputArray): array
	{
		return array_reverse($inputArray);
	}

	/**
	 * @template T
	 * @param array<string, T> $inputArray
	 * @return array<string, T>
	 */
	public function getItemsKeyStartsWith(array $inputArray, string $startWith): array
	{
		return array_filter($inputArray, function ($key) use ($startWith) {
			return str_starts_with((string) $key, $startWith);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @template T
	 * @param array<string, T> $inputArray
	 * @return array<string, T>
	 */
	public function getItemsKeyContains(array $inputArray, string $containString): array
	{
		return array_filter($inputArray, function ($key) use ($containString) {
			return str_contains((string) $key, $containString);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function getItemsValueStartsWith(array $inputArray, string $startWith): array
	{
		return array_filter($inputArray, function ($value) use ($startWith) {
			return is_string($value) && str_starts_with($value, $startWith);
		});
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function getItemsValueContains(array $inputArray, string $containString): array
	{
		return array_filter($inputArray, function ($value) use ($containString) {
			return is_string($value) && str_contains($value, $containString);
		});
	}

	/**
	 * @template T
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function getFirstNElements(array $inputArray, int $count): array
	{
		return array_slice($inputArray, 0, $count);
	}

	/**
	 * @template T
	 * @param T $value
	 * @param array<T> $inputArray
	 * @return array<T>
	 */
	public function addIfNotExistsValue(mixed $value, array $inputArray): array
	{
		// Check if the value does not exist in the array
		if ($this->inArray($value, $inputArray) === false) {
			$inputArray[] = $value;
		}

		return $inputArray;
	}

	/**
	 * @return array<int>
	 */
	public function range(int $start, int $end): array
	{
		return range($start, $end);
	}

}
