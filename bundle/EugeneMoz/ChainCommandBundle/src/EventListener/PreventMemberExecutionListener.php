<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\EventListener;

use EugeneMoz\ChainCommandBundle\Service\ChainCommandManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the console.command event to prevent direct execution of chain member commands.
 */
class PreventMemberExecutionListener implements EventSubscriberInterface
{
    private ChainCommandManager $manager;
    private ContainerInterface $container;

    public function __construct(ChainCommandManager $manager, ContainerInterface $container)
    {
        $this->manager = $manager;
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }
        $commandClass = get_class($command);

        $commandServiceId = $this->manager->getServiceIdByClassName($commandClass);
        if (!$commandServiceId) {
            return;
        }

        $isMember = $this->manager->isChainMember($commandServiceId);

        // check if the command being executed is a registered member of a chain
        if ($isMember) {
            $parentServiceId = $this->manager->getParentForMember($commandServiceId);

            // get parent command name for error message
            $parentCommandName = '';
            if ($parentServiceId && $this->container->has($parentServiceId)) {
                $parentCommand = $this->container->get($parentServiceId);
                if ($parentCommand && method_exists($parentCommand, 'getName')) {
                    $parentCommandName = $parentCommand->getName();
                }
            }

            $message = sprintf(
                'Error: %s command is a member of %s command chain and cannot be executed on its own.',
                $command->getName(),
                $parentCommandName
            );
            // Throw an exception to stop the command execution
            throw new RuntimeException($message);
        }
    }
}
