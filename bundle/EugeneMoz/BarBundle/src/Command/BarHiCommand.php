<?php

declare(strict_types=1);

namespace EugeneMoz\BarBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that outputs a greeting message from Bar bundle.
 */
#[AsCommand(name: 'bar:hi', description: 'Bar Hi Command')]
class BarHiCommand extends Command
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Executes the command to display a greeting message.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = 'Hi from Bar!';

        $this->logger->info($message);
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
