<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class ChainCommandTestCase extends KernelTestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        if (!$kernel) {
            throw new \RuntimeException('Kernel could not be booted.');
        }

        $this->application = new Application($kernel);
    }

    /**
     * Gets a command tester for the given command name.
     */
    protected function getCommandTester(string $commandName): CommandTester
    {
        $command = $this->application->find($commandName);

        return new CommandTester($command);
    }
}
