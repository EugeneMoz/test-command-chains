<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ChainCommandCompilerPass implements CompilerPassInterface
{
    public const CHAIN_COMMAND_MANAGER_SERVICE_ID = 'chain_command.manager';
    public const CHAIN_COMMAND_MEMBER_TAG = 'chain_command.member';
    public const PARENT_SERVICES_PARAMETER = 'chain_command.parent_services'; // using in decorator compiler

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::CHAIN_COMMAND_MANAGER_SERVICE_ID)) {
            return;
        }

        $managerDefinition = $container->getDefinition(self::CHAIN_COMMAND_MANAGER_SERVICE_ID);

        // find all commands with chain_command.member tag
        $chainMembers = $container->findTaggedServiceIds(self::CHAIN_COMMAND_MEMBER_TAG);
        $parentServiceIds = [];

        foreach ($chainMembers as $memberId => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['parent'])) {
                    continue;
                }

                $parentServiceId = $tag['parent'];

                try {
                    $memberDefinition = $container->getDefinition($memberId);
                } catch (ServiceNotFoundException $e) {
                    // skip if parent or member service definition not found
                    continue;
                }

                $managerDefinition->addMethodCall(
                    'addChainMember',
                    [$parentServiceId, $memberId]
                );

                // add class mapping for event subscriber
                $memberClassName = $memberDefinition->getClass();
                if ($memberClassName) {
                    $managerDefinition->addMethodCall(
                        'addMemberClassMapping',
                        [$memberClassName, $memberId]
                    );
                }

                // Collect unique parent service IDs
                $parentServiceIds[$parentServiceId] = true;
            }

        }

        $container->setParameter(self::PARENT_SERVICES_PARAMETER, array_keys($parentServiceIds));
    }
}
