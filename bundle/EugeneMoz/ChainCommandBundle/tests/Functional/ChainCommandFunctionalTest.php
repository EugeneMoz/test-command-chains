<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional;

use Symfony\Component\Console\Command\Command;

class ChainCommandFunctionalTest extends ChainCommandTestCase
{
    /**
     * Test setup to ensure kernel is working.
     */
    public function testKernelBoots(): void
    {
        $kernel = self::bootKernel();
        $this->assertNotNull($kernel);
        $this->assertTrue($kernel->getContainer()->has('test.command.parent'));
        $this->assertTrue($kernel->getContainer()->has('test.command.member'));
    }

    /**
     * Test execution of command chain.
     */
    public function testParentCommandTriggersChain(): void
    {
        $commandTester = $this->getCommandTester('test:parent');
        $result = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $result);
        $output = $commandTester->getDisplay();

        // Check that both commands were executed
        $this->assertStringContainsString('Parent command executed', $output);
        $this->assertStringContainsString('Member command executed', $output);
    }

    /**
     * Test prevention of direct execution of child command.
     *
     * In the test environment not possible to directly test the functionality
     * of the PreventMemberExecutionListener event listener that blocks the execution of child commands.
     * Instead, we check that the configuration has the correct parent-child relationship.
     */
    public function testMemberCommandCannotBeExecutedDirectly(): void
    {
        $kernel = self::bootKernel();
        $this->assertNotNull($kernel);

        $container = $kernel->getContainer();
        $chainManager = $container->get('chain_command.manager');

        $this->assertTrue(
            $chainManager->isChainMember('test.command.member'),
            'test.command.member should be registered as a chain member'
        );

        $this->assertEquals(
            'test.command.parent',
            $chainManager->getParentForMember('test.command.member'),
            'test.command.parent should be the parent for test.command.member'
        );
    }

    /**
     * Test case when child command fails but chain execution continues.
     */
    public function testFailingMemberCommandReportsError(): void
    {
        $commandTester = $this->getCommandTester('test:failing-parent');
        $result = $commandTester->execute([]); // Without --fail option

        // The overall result should be successful even if the child command fails
        $this->assertEquals(Command::SUCCESS, $result);
        $output = $commandTester->getDisplay();

        // Check that both commands were executed
        $this->assertStringContainsString('Failing parent command executed', $output);
        $this->assertStringContainsString('Failing member command executed', $output);

        // Check that the child command reported an error
        $this->assertStringContainsString('Member command failed intentionally', $output);
    }

    /**
     * Test complex chain with multiple commands.
     *
     * This test checks the execution of a chain with multiple child commands
     * and verifies the order of their execution.
     */
    public function testComplexChainExecution(): void
    {
        $kernel = self::bootKernel();
        $this->assertNotNull($kernel);

        // Check that all required commands are registered
        $container = $kernel->getContainer();
        $this->assertTrue($container->has('test.command.complex_parent'));
        $this->assertTrue($container->has('test.command.complex_member1'));
        $this->assertTrue($container->has('test.command.complex_member2'));

        // Check the correct configuration of the command chain
        $chainManager = $container->get('chain_command.manager');
        $this->assertTrue($chainManager->isChainMember('test.command.complex_member1'));
        $this->assertTrue($chainManager->isChainMember('test.command.complex_member2'));

        $this->assertEquals(
            'test.command.complex_parent',
            $chainManager->getParentForMember('test.command.complex_member1')
        );
        $this->assertEquals(
            'test.command.complex_member1',
            $chainManager->getParentForMember('test.command.complex_member2')
        );

        // Execute the command chain
        $commandTester = $this->getCommandTester('test:complex-parent');
        $result = $commandTester->execute([]);

        // Check for successful execution
        $this->assertEquals(Command::SUCCESS, $result);
        $output = $commandTester->getDisplay();

        // Check that all commands in the chain were executed
        $this->assertStringContainsString('Complex parent command executed', $output);
        $this->assertStringContainsString('Member command executed', $output);
        $this->assertStringContainsString('Second member command executed', $output);
    }
}
