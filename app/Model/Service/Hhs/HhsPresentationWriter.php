<?php declare(strict_types=1);

namespace App\Model\Service\Hhs;

use App\Model\DTO\Hhs\HhsPresentationData;
use DateTimeImmutable;
use DOMDocument;
use Nette\Utils\Image;
use Nette\Utils\ImageType;
use RuntimeException;
use ZipArchive;

final class HhsPresentationWriter
{
	private const EmuPerPoint = 12700;
	private const PhotoInset = 4 * self::EmuPerPoint;

	public function fill(ZipArchive $zip, HhsPresentationData $data, DateTimeImmutable $generatedAt, ?string $photoPath): void
	{
		$date = $generatedAt->format('d.m.Y');
		$cover = new PresentationXmlPart($zip, 'ppt/slides/slide1.xml');
		$cover->fillPlaceholders([
			22 => $data->assessment,
			19 => $date,
		]);
		$coverLayout = new PresentationXmlPart($zip, 'ppt/slideLayouts/slideLayout1.xml');
		$this->fillCoverDetails($cover, $coverLayout, $data);
		if ($photoPath !== null) {
			$this->addPhoto($zip, $cover, $coverLayout, $photoPath);
		}
		$coverLayout->save();
		$cover->save();

		$overview = new PresentationXmlPart($zip, 'ppt/slides/slide2.xml');
		$overview->fillPlaceholders([
			10 => $date,
			13 => $data->smoker,
			14 => $data->drivingLicence,
			15 => $data->nightWork,
			16 => $data->height,
			17 => $data->weight,
			19 => $data->positions[0]['period'] ?? '',
			20 => $data->positions[0]['country'] ?? '',
			21 => $data->positions[0]['city'] ?? '',
			23 => $data->positions[1]['period'] ?? '',
			24 => $data->positions[1]['country'] ?? '',
			25 => $data->positions[1]['city'] ?? '',
			31 => $data->notes,
		], singleLineWidths: [19 => 15, 20 => 19, 21 => 19, 23 => 15, 24 => 19, 25 => 19]);
		$overview->save();

		$costs = new PresentationXmlPart($zip, 'ppt/slides/slide3.xml');
		$costs->fillPlaceholders([
			10 => $date,
			16 => 'Muttersprache',
			17 => $data->otherLanguages,
			18 => $data->germanGrade,
			20 => $data->monthlyCosts,
			28 => $data->author,
			29 => $data->contactPhone,
		]);
		$costs->save();

		// These values are printed by the slide layout itself, underneath the slide objects.
		$layout = new PresentationXmlPart($zip, 'ppt/slideLayouts/slideLayout4.xml');
		$layout->fillTableCell(19, 2, 2, $data->travelCosts);
		foreach ([2, 3, 4] as $row) {
			$layout->fillTableCell(23, $row, 3, $data->holidayRate);
		}
		$layout->save();
	}


	private function fillCoverDetails(PresentationXmlPart $cover, PresentationXmlPart $layout, HhsPresentationData $data): void
	{
		// Keep both runs in one native paragraph so their baseline is shared in every viewer.
		$labels = $layout->element("//p:sp[p:nvSpPr/p:cNvPr[@id='20']]");
		$labelTransform = $layout->element('p:spPr/a:xfrm', $labels);
		$valueTransform = $layout->element("//p:sp[p:nvSpPr/p:nvPr/p:ph[@idx='13']]/p:spPr/a:xfrm");
		$valueTab = (int) $layout->element('a:off', $valueTransform)->getAttribute('x')
			- (int) $layout->element('a:off', $labelTransform)->getAttribute('x');
		$valueWidth = (int) $layout->element('a:ext', $valueTransform)->getAttribute('cx');
		$layout->element('a:ext', $labelTransform)->setAttribute('cx', (string) ($valueTab + $valueWidth));
		$layout->element('p:txBody/a:bodyPr', $labels)->setAttribute('wrap', 'none');

		$values = [13 => $data->fullName, 16 => $data->birthday, 17 => $data->city, 18 => $data->phone, 15 => $data->availableFrom];
		$row = 0;
		foreach ($values as $index => $value) {
			$value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', PresentationXmlPart::cleanText($value));
			$paragraph = $layout->element('p:txBody/a:p[' . ++$row . ']', $labels);
			$properties = $layout->element('a:pPr', $paragraph);
			$properties->setAttribute('fontAlgn', 'base');
			$tabs = $layout->document->createElementNS(PresentationXmlPart::Drawing, 'a:tabLst');
			$properties->appendChild($tabs);
			// Retain the template's 36-point tab stops up to the colon, then start the value column.
			foreach ([36 * self::EmuPerPoint, 72 * self::EmuPerPoint, 108 * self::EmuPerPoint, $valueTab] as $position) {
				$tab = $layout->document->createElementNS(PresentationXmlPart::Drawing, 'a:tab');
				$tab->setAttribute('pos', (string) $position);
				$tabs->appendChild($tab);
			}
			$layout->element('a:r/a:t', $paragraph)->appendChild($layout->document->createTextNode("\t"));
			$run = $layout->document->createElementNS(PresentationXmlPart::Drawing, 'a:r');
			$style = $layout->document->createElementNS(PresentationXmlPart::Drawing, 'a:rPr');
			$fontSize = intdiv(1400 * 25, max(25, mb_strwidth($value, 'UTF-8')));
			$style->setAttribute('sz', (string) max(100, $fontSize));
			$run->appendChild($style);
			$text = $layout->document->createElementNS(PresentationXmlPart::Drawing, 'a:t');
			$text->appendChild($layout->document->createTextNode($value));
			$run->appendChild($text);
			$paragraph->appendChild($run);

			$cover->element("//p:sp[p:nvSpPr/p:nvPr/p:ph[@idx='$index']]")->remove();
		}
		$cover->element('//p:spTree')->appendChild($cover->document->importNode($labels, true));
		$labels->remove();
	}


	private function addPhoto(ZipArchive $zip, PresentationXmlPart $cover, PresentationXmlPart $layout, string $photoPath): void
	{
		$frame = $layout->element("//p:sp[p:nvSpPr/p:cNvPr[@id='26']]");
		$transform = $cover->document->importNode($layout->element('p:spPr/a:xfrm', $frame), true);
		$offset = $cover->element('a:off', $transform);
		$size = $cover->element('a:ext', $transform);
		foreach (['x' => 'cx', 'y' => 'cy'] as $axis => $dimension) {
			$offset->setAttribute($axis, (string) ((int) $offset->getAttribute($axis) + self::PhotoInset));
			$size->setAttribute($dimension, (string) ((int) $size->getAttribute($dimension) - 2 * self::PhotoInset));
		}
		$width = 640;
		$height = (int) round($width * (int) $size->getAttribute('cy') / (int) $size->getAttribute('cx'));
		$image = Image::fromFile($photoPath)->resize($width, $height, Image::Cover);
		if (!$zip->addFromString('ppt/media/hhs-profile.jpeg', $image->toString(ImageType::JPEG, 90))) {
			throw new RuntimeException('Cannot embed the profile photograph.');
		}

		$rels = new PresentationXmlPart($zip, 'ppt/slides/_rels/slide1.xml.rels');
		$rel = $rels->document->createElementNS(PresentationXmlPart::Relationships, 'Relationship');
		$rel->setAttribute('Id', 'rIdHhsProfile');
		$rel->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
		$rel->setAttribute('Target', '../media/hhs-profile.jpeg');
		$rels->element('/*')->appendChild($rel);
		$rels->save();

		$picture = new DOMDocument;
		$picture->loadXML(<<<'XML'
			<p:pic xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
				xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
				xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
				<p:nvPicPr><p:cNvPr id="100" name="Profilfoto"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr>
				<p:blipFill><a:blip r:embed="rIdHhsProfile"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
				<p:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr>
			</p:pic>
			XML);
		$shape = $cover->document->importNode($picture->firstElementChild ?? throw new RuntimeException('Invalid picture markup.'), true);
		$properties = $cover->element('p:spPr', $shape);
		$properties->insertBefore($transform, $properties->firstChild);
		$cover->element('//p:spTree')->appendChild($shape);
	}
}
