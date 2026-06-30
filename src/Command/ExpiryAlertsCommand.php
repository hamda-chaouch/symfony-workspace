<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:expiry-alerts',
    description: 'Add a short description for your command',
)]
class ExpiryAlertsCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $alerts = $this->stockRepo->findExpiryAlerts(30);
        if (empty($alerts['expired']) && empty($alerts['soon'])) {
            $output->writeln('Aucune alerte.');
            return Command::SUCCESS;
        }

        $html = $this->renderView('emails/expiry_alerts.html.twig', $alerts);
        $email = (new Email())
            ->from('noreply@devengineering.com')
            ->to('admin@devengineering.com')
            ->subject('Alertes péremption – Parapharmacie')
            ->html($html);

        $this->mailer->send($email);
        $output->writeln('Email envoyé.');
        return Command::SUCCESS;
    }
}
