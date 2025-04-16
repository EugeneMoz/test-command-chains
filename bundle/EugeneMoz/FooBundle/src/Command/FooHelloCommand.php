<?php

declare(strict_types=1);

namespace EugeneMoz\FooBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that outputs a greeting message from Foo bundle.
 */
#[AsCommand(name: 'foo:hello', description: 'Foo Hello Command')]
class FooHelloCommand extends Command
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
        $message = 'Hello from Foo!';

        $this->logger->info($message);
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
