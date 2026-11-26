<?php declare(strict_types=1);

namespace Tests\Unit\Model\Enum;

use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Table\MissingRegistryTableMap;
use ReflectionClass;
use Tests\Support\PHPUnit\TestCase;

final class MissingTypeTest extends TestCase
{
	public function testEveryTypeColumnOfTheTableHasExactlyOneCase(): void
	{
		$typeColumns = [];
		foreach ((new ReflectionClass(MissingRegistryTableMap::class))->getConstants() as $name => $value) {
			if (str_starts_with($name, 'COL_TYPE_')) {
				$typeColumns[] = $value;
			}
		}
		$mapped = array_map(static fn (MissingType $type): string => $type->column(), MissingType::cases());

		sort($typeColumns);
		sort($mapped);

		self::assertSame($typeColumns, $mapped);
	}

	public function testFieldNamesAreTheAutosaveFieldNames(): void
	{
		self::assertSame(
			['typePn', 'typeOcr', 'typeLekar', 'typeSviatok', 'typeZastup', 'typeSluzba', 'typeDovolenka'],
			array_map(static fn (MissingType $type): string => $type->field(), MissingType::cases()),
		);
	}

	public function testDutyAndSubstitutionCountCalendarDays(): void
	{
		$calendarDayTypes = array_values(array_filter(
			MissingType::cases(),
			static fn (MissingType $type): bool => !$type->countsWorkingDaysOnly(),
		));

		self::assertSame([MissingType::ZASTUP, MissingType::SLUZBA], $calendarDayTypes);
	}

	public function testEveryTypeHasItsOwnLabelAndColour(): void
	{
		$labels = [];
		$colors = [];
		foreach (MissingType::cases() as $type) {
			$labels[] = $type->label();
			$colors[] = $type->color();
			self::assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $type->color());
		}

		self::assertSame($labels, array_unique($labels));
		self::assertSame($colors, array_unique($colors));
		self::assertSame(array_combine(
			array_map(static fn (MissingType $type): string => $type->value, MissingType::cases()),
			$labels,
		), MissingType::options());
	}
}
