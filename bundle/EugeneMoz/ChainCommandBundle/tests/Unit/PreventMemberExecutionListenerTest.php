<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Unit;

use EugeneMoz\ChainCommandBundle\EventListener\PreventMemberExecutionListener;
use EugeneMoz\ChainCommandBundle\Service\ChainCommandManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class PreventMemberExecutionListenerTest extends TestCase
{
    private ChainCommandManager|MockObject $manager;
    private ContainerInterface|MockObject $container;
    private PreventMemberExecutionListener $listener;
    private Command|MockObject $command;
    private InputInterface|MockObject $input;
    private OutputInterface|MockObject $output;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ChainCommandManager::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->listener = new PreventMemberExecutionListener($this->manager, $this->container);

        $this->command = $this->createMock(Command::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }

    /**
     * Tests that the listener prevents execution of chain member commands.
     */
    public function testOnConsoleCommandDisablesChainMembers(): void
    {
        // Set up command class
        $commandClass = get_class($this->command);

        // Configure mocks to identify command as a chain member
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('member:command');

        $this->manager->expects($this->once())
            ->method('getServiceIdByClassName')
            ->with($commandClass)
            ->willReturn('member.service.id');

        $this->manager->expects($this->once())
            ->method('isChainMember')
            ->with('member.service.id')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getParentForMember')
            ->with('member.service.id')
            ->willReturn('parent.service.id');

        // Configure container to return parent command
        $parentCommand = $this->createMock(Command::class);
        $parentCommand->expects($this->once())
            ->method('getName')
            ->willReturn('parent:command');

        $this->container->expects($this->once())
            ->method('has')
            ->with('parent.service.id')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('parent.service.id')
            ->willReturn($parentCommand);

        // Create event
        $event = new ConsoleCommandEvent(
            $this->command,
            $this->input,
            $this->output
        );

        // Expect RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error: member:command command is a member of parent:command command chain and cannot be executed on its own.');

        // Call listener
        $this->listener->onConsoleCommand($event);
    }

    /**
     * Tests that the listener does not affect regular commands.
     */
    public function testOnConsoleCommandAllowsRegularCommands(): void
    {
        // Set up command class
        $commandClass = get_class($this->command);

        // Configure manager mock
        $this->manager->expects($this->once())
            ->method('getServiceIdByClassName')
            ->with($commandClass)
            ->willReturn('command.service.id');

        $this->manager->expects($this->once())
            ->method('isChainMember')
            ->with('command.service.id')
            ->willReturn(false);

        // Create event
        $event = new ConsoleCommandEvent(
            $this->command,
            $this->input,
            $this->output
        );

        // Call listener (should not throw exception)
        $this->listener->onConsoleCommand($event);

        // If we reach this point without exceptions, the test is passed
        $this->assertTrue(true);
    }

    /**
     * Test for case when serviceId is not found.
     */
    public function testOnConsoleCommandWithNoServiceId(): void
    {
        // Set up command class
        $commandClass = get_class($this->command);

        // Configure manager mock
        $this->manager->expects($this->once())
            ->method('getServiceIdByClassName')
            ->with($commandClass)
            ->willReturn(null);

        // isChainMember method should not be called
        $this->manager->expects($this->never())
            ->method('isChainMember');

        // Create event
        $event = new ConsoleCommandEvent(
            $this->command,
            $this->input,
            $this->output
        );

        // Call listener (should not throw exception)
        $this->listener->onConsoleCommand($event);

        // If we reach this point without exceptions, the test is passed
        $this->assertTrue(true);
    }
}
