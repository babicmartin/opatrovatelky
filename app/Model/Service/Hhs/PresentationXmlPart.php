<?php declare(strict_types=1);

namespace App\Model\Service\Hhs;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class PresentationXmlPart
{
	public const string Drawing = 'http://schemas.openxmlformats.org/drawingml/2006/main';
	public const string Presentation = 'http://schemas.openxmlformats.org/presentationml/2006/main';
	public const string Relationships = 'http://schemas.openxmlformats.org/package/2006/relationships';

	public readonly DOMDocument $document;
	private readonly DOMXPath $xpath;


	public function __construct(private readonly ZipArchive $zip, private readonly string $path)
	{
		$this->document = new DOMDocument;
		$xml = $zip->getFromName($path);
		if ($xml === false || !$this->document->loadXML($xml, LIBXML_NONET)) {
			throw new RuntimeException("Cannot read presentation part $path.");
		}

		$this->xpath = new DOMXPath($this->document);
		$this->xpath->registerNamespace('a', self::Drawing);
		$this->xpath->registerNamespace('p', self::Presentation);
	}


	public function element(string $query, ?DOMNode $context = null): DOMElement
	{
		$nodes = $this->xpath->query($query, $context);
		$element = $nodes === false ? null : $nodes->item(0);
		if (!$element instanceof DOMElement || $nodes->length !== 1) {
			throw new RuntimeException("Unexpected HHS template structure in $this->path ($query).");
		}

		return $element;
	}


	/**
	 * @param array<int, string> $values  Slide placeholder indices from the HHS template.
	 * @param array<int, int> $singleLineWidths  Text lengths that fit the original single-line fields at full size.
	 */
	public function fillPlaceholders(array $values, array $singleLineWidths = []): void
	{
		foreach ($values as $index => $value) {
			$body = $this->element("//p:sp[p:nvSpPr/p:nvPr/p:ph[@idx='$index']]/p:txBody");
			$this->fillParagraph($this->element('a:p', $body), $value);
			$width = $singleLineWidths[$index] ?? null;
			$length = mb_strwidth($value, 'UTF-8');
			if ($width !== null && $length > $width) {
				$fit = $this->document->createElementNS(self::Drawing, 'a:normAutofit');
				$fit->setAttribute('fontScale', (string) intdiv(100000 * $width, $length));
				$this->element('a:bodyPr', $body)->appendChild($fit);
			}
		}
	}


	public function fillTableCell(int $shapeId, int $row, int $column, string $value): void
	{
		$table = "//p:graphicFrame[p:nvGraphicFramePr/p:cNvPr[@id='$shapeId']]//a:tbl";
		$this->fillParagraph($this->element("$table/a:tr[$row]/a:tc[$column]/a:txBody/a:p"), $value);
	}


	public function save(): void
	{
		$xml = $this->document->saveXML();
		if ($xml === false || !$this->zip->addFromString($this->path, $xml)) {
			throw new RuntimeException("Cannot update presentation part $this->path.");
		}
	}


	public static function cleanText(string $value): string
	{
		return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';
	}


	private function fillParagraph(DOMElement $paragraph, string $value): void
	{
		// Both empty placeholders and date fields carry character formatting in the source.
		$styles = $this->xpath->query('a:r/a:rPr | a:fld/a:rPr | a:endParaRPr', $paragraph);
		$style = $styles === false ? null : $styles->item(0);
		$runProperties = $this->document->createElementNS(self::Drawing, 'a:rPr');
		if ($style instanceof DOMElement) {
			foreach ($style->attributes as $attribute) {
				$runProperties->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $attribute->value);
			}
			foreach ($style->childNodes as $child) {
				$runProperties->appendChild($child->cloneNode(true));
			}
		}

		foreach (iterator_to_array($paragraph->childNodes) as $child) {
			if ($child instanceof DOMElement && in_array($child->localName, ['r', 'fld', 'br'], true)) {
				$paragraph->removeChild($child);
			}
		}

		$end = $paragraph->getElementsByTagNameNS(self::Drawing, 'endParaRPr')->item(0);
		foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", self::cleanText($value))) as $index => $line) {
			if ($index > 0) {
				$paragraph->insertBefore($this->document->createElementNS(self::Drawing, 'a:br'), $end);
			}
			if ($line !== '') {
				$run = $this->document->createElementNS(self::Drawing, 'a:r');
				$run->appendChild($runProperties->cloneNode(true));
				$text = $this->document->createElementNS(self::Drawing, 'a:t');
				$text->appendChild($this->document->createTextNode($line));
				$run->appendChild($text);
				$paragraph->insertBefore($run, $end);
			}
		}
	}
}
