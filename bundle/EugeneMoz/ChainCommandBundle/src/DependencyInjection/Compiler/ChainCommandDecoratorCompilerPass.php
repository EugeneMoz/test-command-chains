<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler;

use EugeneMoz\ChainCommandBundle\Command\CommandDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ChainCommandDecoratorCompilerPass implements CompilerPassInterface
{
    public const MANAGER_SERVICE_ID = 'chain_command.manager';
    public const CONSOLE_COMMAND_TAG = 'console.command';
    public const LOGGER_SERVICE_ID = 'logger';
    public const CONTAINER_SERVICE_ID = 'service_container';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER)) {
            return;
        }

        $parentServiceIds = $container->getParameter(ChainCommandCompilerPass::PARENT_SERVICES_PARAMETER);

        foreach ($parentServiceIds as $originalServiceId) {
            if (!$container->hasDefinition($originalServiceId)) {
                continue;
            }

            $originalDefinition = $container->getDefinition($originalServiceId);
            $decoratorId = $originalServiceId.'.decorator';

            $container->setDefinition($decoratorId, new Definition(
                CommandDecorator::class,
                [
                    new Reference($decoratorId.'.inner'),
                    new Reference(self::MANAGER_SERVICE_ID),
                    new Reference(self::LOGGER_SERVICE_ID),
                    new Reference(self::CONTAINER_SERVICE_ID),
                    $originalServiceId,
                ]
            ))
            ->setDecoratedService($originalServiceId, $decoratorId.'.inner')
            ->setPublic(true)
            ->setAutowired(false)
            ->setTags($originalDefinition->getTags());

            // use decorator only for commands
            if (!isset($container->getDefinition($decoratorId)->getTags()[self::CONSOLE_COMMAND_TAG])) {
                $tags = $originalDefinition->getTag(self::CONSOLE_COMMAND_TAG);
                $container->getDefinition($decoratorId)->addTag(self::CONSOLE_COMMAND_TAG, $tags[0] ?? []);
            }
        }
    }
}
