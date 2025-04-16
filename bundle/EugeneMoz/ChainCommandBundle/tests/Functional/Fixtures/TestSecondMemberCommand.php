<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:second-member',
    description: 'Test second member command',
)]
class TestSecondMemberCommand extends Command
{
    private bool $executed = false;
    private int $executionOrder = 0;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executed = true;
        $output->writeln('Second member command executed');

        return Command::SUCCESS;
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }

    public function setExecutionOrder(int $order): void
    {
        $this->executionOrder = $order;
    }

    public function getExecutionOrder(): int
    {
        return $this->executionOrder;
    }
}
