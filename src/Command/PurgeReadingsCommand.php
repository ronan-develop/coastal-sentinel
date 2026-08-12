<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\EnvironmentReadingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-readings',
    description: 'Purge le rawPayload JSON des relevés ingérés au-delà d\'une fenêtre de rétention.',
)]
final class PurgeReadingsCommand extends Command
{
    public function __construct(
        private readonly EnvironmentReadingRepository $environmentReadingRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // Purge uniquement le rawPayload (debug technique court terme) — les
        // colonnes structurées, nécessaires au RiskEngine et à la future
        // calibration des seuils, ne sont pas concernées par cette commande
        // (cf. .claude/architecture.md §4).
        $this->addOption('keep-days', null, InputOption::VALUE_REQUIRED, 'Nombre de jours de rawPayload à conserver', '90');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $keepDays = (int) $input->getOption('keep-days');

        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $keepDays));
        $purgedCount = $this->environmentReadingRepository->purgeRawPayloadOlderThan($threshold);

        $io->success(\sprintf('%d relevé(s) purgé(s) (rawPayload effacé, au-delà de %d jours).', $purgedCount, $keepDays));

        return Command::SUCCESS;
    }
}
