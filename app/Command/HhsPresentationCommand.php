<?php declare(strict_types=1);

namespace App\Command;

use App\Model\Service\Hhs\HhsPresentationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'babysitter:hhs-presentation', description: 'Vytvorí partnerskú prezentáciu HHS pre opatrovateľku.')]
final class HhsPresentationCommand extends Command
{
	public function __construct(private readonly HhsPresentationService $service)
	{
		parent::__construct();
	}


	protected function configure(): void
	{
		$this->addArgument('id', InputArgument::REQUIRED, 'ID opatrovateľky');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$id = filter_var($input->getArgument('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) {
			$output->writeln('<error>ID opatrovateľky musí byť kladné celé číslo.</error>');
			return self::INVALID;
		}

		$output->writeln($this->service->generate($id), OutputInterface::OUTPUT_RAW);
		return self::SUCCESS;
	}
}
