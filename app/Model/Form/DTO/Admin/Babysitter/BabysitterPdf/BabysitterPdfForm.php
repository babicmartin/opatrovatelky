<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Babysitter\BabysitterPdf;

final readonly class BabysitterPdfForm
{
	public function __construct(
		public int $id,
		public int $profilShowContact,
	) {
	}
}
