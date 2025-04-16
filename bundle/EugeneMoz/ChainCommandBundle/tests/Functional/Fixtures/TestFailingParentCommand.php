<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:failing-parent',
    description: 'Test failing parent command',
)]
class TestFailingParentCommand extends Command
{
    private bool $executed = false;
    private bool $shouldFail = false;

    protected function configure(): void
    {
        $this->addOption('fail', null, InputOption::VALUE_NONE, 'Force the command to fail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executed = true;
        $this->shouldFail = $input->getOption('fail');

        $output->writeln('Failing parent command executed');

        if ($this->shouldFail) {
            $output->writeln('Command failed intentionally');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }

    public function hasFailed(): bool
    {
        return $this->shouldFail;
    }
}
