<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Unit;

use EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler\ChainCommandCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ChainCommandCompilerPassTest extends TestCase
{
    private ChainCommandCompilerPass $compilerPass;
    private ContainerBuilder $container;
    private Definition $managerDefinition;

    protected function setUp(): void
    {
        $this->compilerPass = new ChainCommandCompilerPass();
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->managerDefinition = $this->createMock(Definition::class);
    }

    /**
     * Test successful registration of chain command members.
     */
    public function testProcessWithChainMembers(): void
    {
        // Create definitions
        $member1Definition = $this->createMock(Definition::class);
        $member2Definition = $this->createMock(Definition::class);

        // Configure class return for services
        $member1Definition->method('getClass')
            ->willReturn('TestMember1Class');

        $member2Definition->method('getClass')
            ->willReturn('TestMember2Class');

        // Configure container mock
        $this->container->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(true);

        $this->container->method('getDefinition')
            ->willReturnCallback(function ($serviceId) use ($member1Definition, $member2Definition) {
                if (ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID === $serviceId) {
                    return $this->managerDefinition;
                } elseif ('member1.service.id' === $serviceId) {
                    return $member1Definition;
                } elseif ('member2.service.id' === $serviceId) {
                    return $member2Definition;
                }

                return null;
            });

        // Found two services with chain_command.member tags
        $this->container->method('findTaggedServiceIds')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MEMBER_TAG)
            ->willReturn([
                'member1.service.id' => [
                    ['parent' => 'parent1.service.id'],
                ],
                'member2.service.id' => [
                    ['parent' => 'parent1.service.id'],
                    ['parent' => 'parent2.service.id'],
                ],
            ]);

        // Check expected method calls on manager
        $this->managerDefinition->expects($this->exactly(6))
            ->method('addMethodCall')
            ->willReturnCallback(function ($method, $args) {
                static $callIndex = 0;

                // Expected calls in order of appearance
                $expectedCalls = [
                    ['addChainMember', ['parent1.service.id', 'member1.service.id']],
                    ['addMemberClassMapping', ['TestMember1Class', 'member1.service.id']],
                    ['addChainMember', ['parent1.service.id', 'member2.service.id']],
                    ['addMemberClassMapping', ['TestMember2Class', 'member2.service.id']],
                    ['addChainMember', ['parent2.service.id', 'member2.service.id']],
                    ['addMemberClassMapping', ['TestMember2Class', 'member2.service.id']],
                ];

                // Check that current call matches expected
                $this->assertEquals($expectedCalls[$callIndex][0], $method);
                $this->assertEquals($expectedCalls[$callIndex][1], $args);

                ++$callIndex;

                return $this->managerDefinition;
            });

        // Check that parent services parameter is set
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(
                ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER,
                $this->callback(function ($value) {
                    return is_array($value)
                          && 2 === count($value)
                          && in_array('parent1.service.id', $value)
                          && in_array('parent2.service.id', $value);
                })
            );

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test: manager not found in container.
     */
    public function testProcessWithoutManager(): void
    {
        // Manager not found
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(false);

        // Check that findTaggedServiceIds method is not called
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        // Run compiler pass - should be no exceptions
        $this->compilerPass->process($this->container);
        $this->assertTrue(true); // If we reach this point, there were no exceptions
    }

    /**
     * Negative test: service definition not found error.
     */
    public function testProcessWithServiceNotFoundException(): void
    {
        // Configure container mock
        $this->container->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(true);

        // Return manager and throw exception for member
        $this->container->method('getDefinition')
            ->willReturnCallback(function ($serviceId) {
                if (ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID === $serviceId) {
                    return $this->managerDefinition;
                }
                if ('member.service.id' === $serviceId) {
                    throw new ServiceNotFoundException('member.service.id');
                }

                return null;
            });

        // Found service with chain_command.member tag
        $this->container->method('findTaggedServiceIds')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MEMBER_TAG)
            ->willReturn([
                'member.service.id' => [
                    ['parent' => 'parent.service.id'],
                ],
            ]);

        // Check that manager methods are not called
        $this->managerDefinition->expects($this->never())
            ->method('addMethodCall');

        // Parent services parameter should be empty array
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(
                ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER,
                []
            );

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test: chain member without parent specification.
     */
    public function testProcessWithMissingParent(): void
    {
        // Configure container mock
        $this->container->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(true);

        $this->container->method('getDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn($this->managerDefinition);

        // Found service with chain_command.member tag, but without parent
        $this->container->method('findTaggedServiceIds')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MEMBER_TAG)
            ->willReturn([
                'member.service.id' => [
                    ['some_attribute' => 'value'],
                ],
            ]);

        // No addMethodCall calls should happen
        $this->managerDefinition->expects($this->never())
            ->method('addMethodCall');

        // Parent services parameter should be empty array
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(
                ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER,
                []
            );

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test: no services with chain_command.member tag.
     */
    public function testProcessWithNoChainMembers(): void
    {
        // Configure container mock
        $this->container->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(true);

        $this->container->method('getDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn($this->managerDefinition);

        // No services with chain_command.member tag
        $this->container->method('findTaggedServiceIds')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MEMBER_TAG)
            ->willReturn([]);

        // No addMethodCall calls should happen
        $this->managerDefinition->expects($this->never())
            ->method('addMethodCall');

        // Parent services parameter should be empty array
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(
                ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER,
                []
            );

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test for case when service class is not defined.
     */
    public function testProcessWithoutClassName(): void
    {
        // Service definition
        $memberDefinition = $this->createMock(Definition::class);
        $memberDefinition->method('getClass')
            ->willReturn(null);

        // Configure container mock
        $this->container->method('hasDefinition')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID)
            ->willReturn(true);

        // Configure definitions return
        $this->container->method('getDefinition')
            ->willReturnCallback(function ($serviceId) use ($memberDefinition) {
                if (ChainCommandCompilerPass::CHAIN_COMMAND_MANAGER_SERVICE_ID === $serviceId) {
                    return $this->managerDefinition;
                } elseif ('member.service.id' === $serviceId) {
                    return $memberDefinition;
                }

                return null;
            });

        // Found service with chain_command.member tag
        $this->container->method('findTaggedServiceIds')
            ->with(ChainCommandCompilerPass::CHAIN_COMMAND_MEMBER_TAG)
            ->willReturn([
                'member.service.id' => [
                    ['parent' => 'parent.service.id'],
                ],
            ]);

        // Expect only one addMethodCall for addChainMember, but not for addMemberClassMapping
        $this->managerDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addChainMember', ['parent.service.id', 'member.service.id']);

        // Parent services parameter should be set
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(
                ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER,
                ['parent.service.id']
            );

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }
}
