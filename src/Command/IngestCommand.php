<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GridIngestionOrchestrator;
use App\Service\IngestionOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ingest',
    description: 'Ingère les relevés environnementaux depuis une source externe (ex. Copernicus).',
)]
final class IngestCommand extends Command
{
    public function __construct(
        private readonly IngestionOrchestrator $orchestrator,
        private readonly GridIngestionOrchestrator $gridOrchestrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Nom de la source de données (ex. copernicus)')
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Code de la zone à ingérer', 'rade-de-brest')
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Date de départ (YYYY-MM-DD)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = $input->getOption('source');
        $zoneCode = $input->getOption('zone');
        $since = $input->getOption('since') !== null
            ? new \DateTimeImmutable($input->getOption('since'))
            : new \DateTimeImmutable('today');

        try {
            $count = $this->orchestrator->ingest($source, $zoneCode, $since);
        } catch (\Throwable $exception) {
            $io->error(\sprintf(
                'Échec de l\'ingestion "%s" pour la zone "%s" : %s. Les données précédentes sont conservées.',
                $source,
                $zoneCode,
                $exception->getMessage(),
            ));

            return Command::FAILURE;
        }

        $io->success(\sprintf('%d mesure(s) ingérée(s) pour la zone "%s".', $count, $zoneCode));

        // Instantané de couverture (diagnostic, ticket #33) — jamais
        // bloquant : le calcul de risque ne dépend pas de cette donnée,
        // un échec ici ne doit pas faire échouer l'ingestion principale.
        try {
            $cellCount = $this->gridOrchestrator->ingest($source, $zoneCode);
            $io->note(\sprintf('%d maille(s) de couverture ingérée(s) pour la zone "%s".', $cellCount, $zoneCode));
        } catch (\Throwable $exception) {
            $io->warning(\sprintf('Grille de couverture non disponible pour "%s" : %s.', $source, $exception->getMessage()));
        }

        return Command::SUCCESS;
    }
}
