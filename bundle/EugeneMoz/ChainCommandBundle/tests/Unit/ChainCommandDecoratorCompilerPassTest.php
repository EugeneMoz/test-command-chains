<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Unit;

use EugeneMoz\ChainCommandBundle\Command\CommandDecorator;
use EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler\ChainCommandCompilerPass;
use EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler\ChainCommandDecoratorCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ChainCommandDecoratorCompilerPassTest extends TestCase
{
    private ChainCommandDecoratorCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new ChainCommandDecoratorCompilerPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    /**
     * Test absence of parent services parameter.
     */
    public function testProcessWithoutParentServicesParameter(): void
    {
        $this->container->expects($this->once())
            ->method('hasParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn(false);

        // Check that getParameter and hasDefinition methods are not called
        $this->container->expects($this->never())
            ->method('getParameter');

        $this->container->expects($this->never())
            ->method('hasDefinition');

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test empty parent services list.
     */
    public function testProcessWithEmptyParentServices(): void
    {
        $this->container->expects($this->once())
            ->method('hasParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn([]);

        // Check that hasDefinition method is not called
        $this->container->expects($this->never())
            ->method('hasDefinition');

        // Check that setDefinition method is not called
        $this->container->expects($this->never())
            ->method('setDefinition');

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Test creation of decorators for parent commands.
     */
    public function testProcessWithParentServices(): void
    {
        $parentServiceIds = ['parent1.service.id', 'parent2.service.id'];

        // Configure container mock
        $this->container->expects($this->once())
            ->method('hasParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn($parentServiceIds);

        // Check for service definitions existence
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap([
                ['parent1.service.id', true],
                ['parent2.service.id', true],
            ]);

        // Create definition mocks
        $parent1Definition = $this->createMock(Definition::class);
        $parent2Definition = $this->createMock(Definition::class);
        $decorator1Definition = $this->createMock(Definition::class);
        $decorator2Definition = $this->createMock(Definition::class);

        // Configure getTags for parent commands
        $parent1Definition->expects($this->once())
            ->method('getTags')
            ->willReturn(['console.command' => [['name' => 'test:parent1']]]);

        $parent2Definition->expects($this->once())
            ->method('getTags')
            ->willReturn(['some_other_tag' => [['name' => 'test:parent2']]]);

        // Configure getTag for command without console.command tag
        $parent2Definition->expects($this->once())
            ->method('getTag')
            ->with(ChainCommandDecoratorCompilerPass::CONSOLE_COMMAND_TAG)
            ->willReturn([['name' => 'test:parent2']]);

        // Configure getTags for decorators
        $decorator1Definition->expects($this->once())
            ->method('getTags')
            ->willReturn(['console.command' => [['name' => 'test:parent1']]]);

        $decorator2Definition->expects($this->once())
            ->method('getTags')
            ->willReturn([]);

        // Configure addTag for decorator
        $decorator2Definition->expects($this->once())
            ->method('addTag')
            ->with(ChainCommandDecoratorCompilerPass::CONSOLE_COMMAND_TAG, ['name' => 'test:parent2']);

        // Return definitions when requested
        $this->container->method('getDefinition')
            ->willReturnMap([
                ['parent1.service.id', $parent1Definition],
                ['parent2.service.id', $parent2Definition],
                ['parent1.service.id.decorator', $decorator1Definition],
                ['parent2.service.id.decorator', $decorator2Definition],
            ]);

        // Check creation of new definitions for decorators
        $this->container->expects($this->exactly(2))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'parent1.service.id.decorator',
                    $this->callback(function ($definition) {
                        return $definition instanceof Definition
                               && CommandDecorator::class === $definition->getClass()
                               && $definition->getArgument(0) instanceof Reference
                               && $definition->getArgument(1) instanceof Reference
                               && $definition->getArgument(2) instanceof Reference
                               && $definition->getArgument(3) instanceof Reference
                               && 'parent1.service.id' === $definition->getArgument(4);
                    }),
                ],
                [
                    'parent2.service.id.decorator',
                    $this->callback(function ($definition) {
                        return $definition instanceof Definition
                               && CommandDecorator::class === $definition->getClass()
                               && $definition->getArgument(0) instanceof Reference
                               && $definition->getArgument(1) instanceof Reference
                               && $definition->getArgument(2) instanceof Reference
                               && $definition->getArgument(3) instanceof Reference
                               && 'parent2.service.id' === $definition->getArgument(4);
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls($decorator1Definition, $decorator2Definition);

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }

    /**
     * Negative test: missing service definition.
     */
    public function testProcessWithMissingDefinition(): void
    {
        $parentServiceIds = ['existing.service.id', 'missing.service.id'];

        // Configure container mock
        $this->container->expects($this->once())
            ->method('hasParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)
            ->willReturn($parentServiceIds);

        // One service exists, another doesn't
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap([
                ['existing.service.id', true],
                ['missing.service.id', false],
            ]);

        // Create definition mocks
        $existingDefinition = $this->createMock(Definition::class);
        $decoratorDefinition = $this->createMock(Definition::class);

        // Configure getTags for existing command
        $existingDefinition->expects($this->once())
            ->method('getTags')
            ->willReturn(['console.command' => [['name' => 'test:existing']]]);

        // Configure getTags for decorator
        $decoratorDefinition->expects($this->once())
            ->method('getTags')
            ->willReturn(['console.command' => [['name' => 'test:existing']]]);

        // Return definition only for existing service
        $this->container->method('getDefinition')
            ->willReturnMap([
                ['existing.service.id', $existingDefinition],
                ['existing.service.id.decorator', $decoratorDefinition],
            ]);

        // Check creation of new definition only for existing service
        $this->container->expects($this->once())
            ->method('setDefinition')
            ->with(
                'existing.service.id.decorator',
                $this->callback(function ($definition) {
                    return $definition instanceof Definition
                           && CommandDecorator::class === $definition->getClass();
                })
            )
            ->willReturn($decoratorDefinition);

        // Run compiler pass
        $this->compilerPass->process($this->container);
    }
}
