<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\RiskType;
use App\Repository\ZoneRepository;
use App\Service\RiskEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assess-risk',
    description: 'Calcule le risque thermique d\'une zone à partir des relevés ingérés.',
)]
final class AssessRiskCommand extends Command
{
    public function __construct(
        private readonly RiskEngine $riskEngine,
        private readonly ZoneRepository $zoneRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Code de la zone à évaluer', 'rade-de-brest');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $zoneCode = $input->getOption('zone');

        $zone = $this->zoneRepository->findOneBy(['code' => $zoneCode]);

        if ($zone === null) {
            $io->error(\sprintf('Zone inconnue : "%s".', $zoneCode));

            return Command::FAILURE;
        }

        // Un seul type de risque en MVP (cf. .claude/architecture.md, phase 0).
        $assessment = $this->riskEngine->assess($zone, RiskType::Thermal, new \DateTimeImmutable('today'));

        $io->success(\sprintf(
            'Risque thermique évalué pour "%s" : score=%.1f, action="%s".',
            $zoneCode,
            $assessment->getScore(),
            $assessment->getRecommendedAction(),
        ));

        return Command::SUCCESS;
    }
}
