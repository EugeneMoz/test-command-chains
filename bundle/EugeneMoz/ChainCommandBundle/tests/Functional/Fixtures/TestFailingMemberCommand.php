<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:failing-member',
    description: 'Test failing member command',
)]
class TestFailingMemberCommand extends Command
{
    private bool $executed = false;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executed = true;
        $output->writeln('Failing member command executed');

        // This command always ends with failure
        $output->writeln('Member command failed intentionally');

        return Command::FAILURE;
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }
}
