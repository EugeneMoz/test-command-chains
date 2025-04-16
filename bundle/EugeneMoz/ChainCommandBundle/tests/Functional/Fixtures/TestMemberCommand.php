<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:member',
    description: 'Test member command',
)]
class TestMemberCommand extends Command
{
    private bool $executed = false;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executed = true;
        $output->writeln('Member command executed');

        return Command::SUCCESS;
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }
}
