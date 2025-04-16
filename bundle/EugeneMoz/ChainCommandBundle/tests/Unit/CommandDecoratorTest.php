<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Unit;

use EugeneMoz\ChainCommandBundle\Command\CommandDecorator;
use EugeneMoz\ChainCommandBundle\Service\ChainCommandManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommandDecoratorTest extends TestCase
{
    private Command $originalCommand;
    private ChainCommandManager $manager;
    private LoggerInterface $logger;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        // Create mock objects for reuse
        $this->originalCommand = $this->createMock(Command::class);
        $this->manager = $this->createMock(ChainCommandManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        // Configure mocks
        $this->originalCommand->method('getName')->willReturn('test:command');
    }

    /**
     * Test for correct command decorator setup.
     */
    public function testDecoratorSetup(): void
    {
        // Create decorator
        $decorator = new CommandDecorator(
            $this->originalCommand,
            $this->manager,
            $this->logger,
            $this->container,
            'test.service.id'
        );

        // Check that command name is set correctly
        $this->assertEquals('test:command', $decorator->getName());
    }

    /**
     * Test decorator configuration.
     */
    public function testConfigure(): void
    {
        // Configure original command mock
        $inputDefinition = new InputDefinition([
            new InputOption('option', 'o', InputOption::VALUE_REQUIRED, 'Test option'),
        ]);

        $this->originalCommand->method('getDefinition')->willReturn($inputDefinition);
        $this->originalCommand->method('getHelp')->willReturn('Help text');
        $this->originalCommand->method('getDescription')->willReturn('Command description');

        // Create decorator
        $decorator = $this->getMockBuilder(CommandDecorator::class)
            ->setConstructorArgs([
                $this->originalCommand,
                $this->manager,
                $this->logger,
                $this->container,
                'test.service.id',
            ])
            ->onlyMethods(['setHelp', 'setDescription', 'setDefinition'])
            ->getMock();

        // Check that configuration methods are called with correct arguments
        $decorator->expects($this->once())
            ->method('setHelp')
            ->with('Help text');

        $decorator->expects($this->once())
            ->method('setDescription')
            ->with('Command description');

        $decorator->expects($this->once())
            ->method('setDefinition')
            ->with($inputDefinition);

        // Call configure through reflection
        $reflectionMethod = new \ReflectionMethod(CommandDecorator::class, 'configure');
        $reflectionMethod->invoke($decorator);
    }

    /**
     * Test successful execution of command chain.
     */
    public function testRunWithSuccessfulChain(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        // Configure manager mock to return chain members
        $this->manager->expects($this->once())
            ->method('getChainMembers')
            ->with('test.service.id')
            ->willReturn(['member1.service.id', 'member2.service.id']);

        // Configure command mock for successful execution
        $this->originalCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        // Configure chain member command mocks
        $memberCommand1 = $this->createMock(Command::class);
        $memberCommand1->method('getName')->willReturn('member1:command');
        $memberCommand1->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        $memberCommand2 = $this->createMock(Command::class);
        $memberCommand2->method('getName')->willReturn('member2:command');
        $memberCommand2->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        // Configure container to return chain member commands
        $this->container->method('get')
            ->willReturnCallback(function ($serviceId) use ($memberCommand1, $memberCommand2) {
                if ('member1.service.id' === $serviceId) {
                    return $memberCommand1;
                } elseif ('member2.service.id' === $serviceId) {
                    return $memberCommand2;
                }

                return null;
            });

        // Expect logging
        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $decorator = new CommandDecorator(
            $this->originalCommand,
            $this->manager,
            $this->logger,
            $this->container,
            'test.service.id'
        );

        $result = $decorator->run($input, $output);
        $this->assertEquals(Command::SUCCESS, $result);
    }

    /**
     * Test chain execution when parent command fails.
     */
    public function testRunWithFailedParentCommand(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        // Return chain members list
        $this->manager->expects($this->once())
            ->method('getChainMembers')
            ->with('test.service.id')
            ->willReturn(['member.service.id']);

        // Original command fails
        $this->originalCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::FAILURE);

        // Expect warning logging
        $this->logger->expects($this->atLeastOnce())
            ->method('warning');

        $memberCommand = $this->createMock(Command::class);
        $memberCommand->method('getName')->willReturn('member:command');

        // Configure container
        $this->container->method('get')
            ->with('member.service.id')
            ->willReturn($memberCommand);

        $decorator = new CommandDecorator(
            $this->originalCommand,
            $this->manager,
            $this->logger,
            $this->container,
            'test.service.id'
        );

        $result = $decorator->run($input, $output);
        $this->assertEquals(Command::FAILURE, $result);
    }

    /**
     * Test chain execution with exception in chain member.
     */
    public function testRunWithExceptionInChainMember(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        // Return chain members list
        $this->manager->expects($this->once())
            ->method('getChainMembers')
            ->with('test.service.id')
            ->willReturn(['member.service.id']);

        // Original command executes successfully
        $this->originalCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        // Chain member command throws exception
        $memberCommand = $this->createMock(Command::class);
        $memberCommand->method('getName')->willReturn('member:command');
        $memberCommand->expects($this->once())
            ->method('run')
            ->willThrowException(new \Exception('Test exception'));

        $this->container->method('get')
            ->with('member.service.id')
            ->willReturn($memberCommand);

        // Expect error logging
        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $decorator = new CommandDecorator(
            $this->originalCommand,
            $this->manager,
            $this->logger,
            $this->container,
            'test.service.id'
        );

        $result = $decorator->run($input, $output);
        $this->assertEquals(Command::FAILURE, $result);
    }

    /**
     * Test chain execution with no members.
     */
    public function testRunWithNoChainMembers(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        // Return empty chain members list
        $this->manager->expects($this->once())
            ->method('getChainMembers')
            ->with('test.service.id')
            ->willReturn([]);

        // Original command executes successfully
        $this->originalCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        // Expect info logging about empty chain
        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $decorator = new CommandDecorator(
            $this->originalCommand,
            $this->manager,
            $this->logger,
            $this->container,
            'test.service.id'
        );

        $result = $decorator->run($input, $output);
        $this->assertEquals(Command::SUCCESS, $result);
    }
}
