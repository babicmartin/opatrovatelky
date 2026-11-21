<?php declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Model\Repository\HhsPresentationRepository;
use App\Model\Service\Hhs\HhsPresentationWriter;
use App\Model\Service\Hhs\PresentationXmlPart;
use DateTimeImmutable;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use ZipArchive;

final class HhsPresentationTest extends DatabaseTestCase
{
	public function testGradeConversionAndOptionalFields(): void
	{
		$id = TestDatabase::createBabysitter([
			'phone' => 'hidden-phone', 'profil_show_contact' => 0,
			'hhs_monthly_costs' => 3500, 'hhs_travel_costs' => '1234.50',
		]);
		$table = $this->getDatabase()->table('sn_opatrovatelky');
		$repository = $this->getContainer()->createInstance(HhsPresentationRepository::class);
		$date = new DateTimeImmutable('2026-04-01');
		foreach ([0 => '', 1 => '2', 2 => '2', 3 => '3', 4 => '5', 5 => '4', 6 => '3', 99 => ''] as $level => $grade) {
			$table->where('id', $id)->update(['language_skills' => $level]);
			$data = $repository->find($id, $date);
			self::assertNotNull($data);
			self::assertSame($grade, $data->germanGrade);
			self::assertSame('', $data->phone);
		}

		self::assertSame('3500 €', $data->monthlyCosts);
		self::assertSame('1234,50 €', $data->travelCosts);

		foreach ([[null, ''], [0, 'Nein'], [1, 'Ja']] as [$value, $expected]) {
			$table->where('id', $id)->update(['hhs_night_work' => $value]);
			self::assertSame($expected, $repository->find($id, $date)?->nightWork);
		}
		$table->where('id', $id)->update(['type' => 2]);
		self::assertNull($repository->find($id, $date));
	}


	public function testWritesPartnerDataAndPreservesTemplateParts(): void
	{
		$date = new DateTimeImmutable('2026-09-21');
		$id = TestDatabase::createBabysitter([
			'hhs_monthly_costs' => 0,
			'hhs_travel_costs' => null,
			'hhs_holiday_supplement' => '75%/Tag',
			'hhs_contact_phone' => null,
			'hhs_additional_notes' => "A & B\n<DE>",
		]);
		$familyId = TestDatabase::createFamily(['city' => 'Wien', 'state' => 2]);
		foreach (['02-14', '03-14', '04-14', '12-14'] as $end) {
			TestDatabase::createTurnus([
				'babysitter_id' => $id, 'family_id' => $familyId, 'status' => 10,
				'date_from' => '2026-' . substr($end, 0, 2) . '-01', 'date_to' => '2026-' . $end,
			]);
		}
		$data = $this->getContainer()->createInstance(HhsPresentationRepository::class)->find($id, $date);
		self::assertNotNull($data);
		self::assertSame(['01.04.2026 – 14.04.2026', '01.03.2026 – 14.03.2026'], array_column($data->positions, 'period'));
		self::assertSame('Wien', $data->positions[0]['city']);
		self::assertSame('Osterreich', $data->positions[0]['country']);
		self::assertSame('0 €', $data->monthlyCosts);
		self::assertSame('', $data->travelCosts);

		$template = dirname(__DIR__, 3) . '/private/templates/hhs/HHS_Bewerberprofil_4MS.pptx';
		$copy = tempnam(sys_get_temp_dir(), 'hhs-test-');
		self::assertIsString($copy);
		try {
			self::assertTrue(copy($template, $copy));
			$zip = new ZipArchive;
			self::assertTrue($zip->open($copy) === true);
			(new HhsPresentationWriter)->fill($zip, $data, $date, null);
			self::assertTrue($zip->close());
			self::assertTrue($zip->open($copy, ZipArchive::CHECKCONS) === true);
			$original = new ZipArchive;
			self::assertTrue($original->open($template) === true);
			try {
				self::assertSame($original->numFiles, $zip->numFiles);
				$parts = ['ppt/slideLayouts/slideLayout4.xml'];
				foreach ([1, 2, 3] as $index) {
					$part = "ppt/slides/slide$index.xml";
					$parts[] = $part;
					$slide = (string) $zip->getFromName($part);
					self::assertStringContainsString('21.09.2026', $slide);
					self::assertStringNotContainsString('datetime1', $slide);
				}
				$layout = (string) $zip->getFromName($parts[0]);
				self::assertSame(3, substr_count($layout, '75%/Tag'));
				self::assertStringNotContainsString('340 €', $layout);
				self::assertStringNotContainsString('je 50 €', $layout);
				self::assertStringContainsString('A &amp; B', (string) $zip->getFromName($parts[2]));
				self::assertStringContainsString('&lt;DE&gt;', (string) $zip->getFromName($parts[2]));
				self::assertStringNotContainsString('07134', (string) $zip->getFromName($parts[3]));
				$parts[] = 'ppt/slideLayouts/slideLayout1.xml';
				self::assertFalse($zip->locateName('ppt/media/hhs-profile.jpeg'));
				for ($i = 0; $i < $original->numFiles; $i++) {
					$name = $original->getNameIndex($i);
					self::assertIsString($name);
					if (!in_array($name, $parts, true)) {
						self::assertSame($original->getFromName($name), $zip->getFromName($name), $name);
					}
				}
			} finally {
				$original->close();
				$zip->close();
			}
		} finally {
			unlink($copy);
		}
	}


	public function testCoverKeepsValuesOnLabelBaselinesAndInsetsPhoto(): void
	{
		$date = new DateTimeImmutable('2026-09-21');
		$id = TestDatabase::createBabysitter([
			'name' => 'Anna', 'surname' => 'Example', 'birthday' => '1980-03-04',
			'city' => "Mesto & okolie\n123", 'profil_show_contact' => 0, 'hhs_start_date' => '2026-09-23',
		]);
		$data = $this->getContainer()->getByType(HhsPresentationRepository::class)->find($id, $date);
		self::assertNotNull($data);
		$root = dirname(__DIR__, 3);
		$copy = tempnam(sys_get_temp_dir(), 'hhs-cover-');
		self::assertIsString($copy);
		try {
			self::assertTrue(copy($root . '/private/templates/hhs/HHS_Bewerberprofil_4MS.pptx', $copy));
			$zip = new ZipArchive;
			self::assertTrue($zip->open($copy) === true);
			try {
				(new HhsPresentationWriter)->fill($zip, $data, $date, $root . '/www/img/users/empty.jpg');
			} finally {
				$zip->close();
			}
			self::assertTrue($zip->open($copy, ZipArchive::CHECKCONS) === true);
			try {
				$cover = new PresentationXmlPart($zip, 'ppt/slides/slide1.xml');
				$layout = new PresentationXmlPart($zip, 'ppt/slideLayouts/slideLayout1.xml');
				$rows = $cover->element("//p:sp[p:nvSpPr/p:cNvPr[@id='20']]/p:txBody");
				foreach ([
					['Vor.- Zuname', 'Anna Example'],
					['Geburtsdatum', '04.03.1980'],
					['Wohnort', 'Mesto & okolie 123'],
					['Tel. Nummer', ''],
					['Verfügbar ab', '23.09.2026'],
				] as $index => [$label, $value]) {
					$row = $cover->element('a:p[' . ($index + 1) . ']', $rows);
					self::assertSame('base', $cover->element('a:pPr', $row)->getAttribute('fontAlgn'));
					self::assertStringStartsWith($label, $cover->element('a:r[1]/a:t', $row)->textContent);
					self::assertSame($value, $cover->element('a:r[2]/a:t', $row)->textContent);
				}
				$frame = $layout->element("//p:sp[p:nvSpPr/p:cNvPr[@id='26']]/p:spPr/a:xfrm");
				$photo = $cover->element("//p:pic[p:nvPicPr/p:cNvPr[@name='Profilfoto']]/p:spPr/a:xfrm");
				foreach (['x' => 'cx', 'y' => 'cy'] as $axis => $dimension) {
					$inset = (int) $cover->element('a:off', $photo)->getAttribute($axis)
						- (int) $layout->element('a:off', $frame)->getAttribute($axis);
					self::assertSame(50800, $inset);
					self::assertSame(2 * $inset, (int) $layout->element('a:ext', $frame)->getAttribute($dimension)
						- (int) $cover->element('a:ext', $photo)->getAttribute($dimension));
				}
			} finally {
				$zip->close();
			}
		} finally {
			unlink($copy);
		}
	}
}
