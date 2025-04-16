<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Command;

use EugeneMoz\ChainCommandBundle\Service\ChainCommandManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Decorates Symfony commands to add chain execution capability.
 * This decorator first runs the original command and then executes all registered chain members.
 */
class CommandDecorator extends Command
{
    private Command $originalCommand;
    private ChainCommandManager $manager;
    private LoggerInterface $logger;
    private ContainerInterface $container;
    private string $parentCommandName;
    private string $parentServiceId;

    /**
     * Creates a new command decorator instance.
     */
    public function __construct(
        Command $originalCommand,
        ChainCommandManager $manager,
        LoggerInterface $logger,
        ContainerInterface $container,
        string $parentServiceId,
    ) {
        $this->originalCommand = $originalCommand;
        $this->manager = $manager;
        $this->logger = $logger;
        $this->container = $container;
        $this->parentCommandName = $originalCommand->getName();
        $this->parentServiceId = $parentServiceId;

        parent::__construct($this->parentCommandName);
    }

    /**
     * Configures the decorator by copying settings from the original command.
     */
    protected function configure(): void
    {
        // Copy configuration from the original command
        $this->setHelp($this->originalCommand->getHelp());
        $this->setDescription($this->originalCommand->getDescription());
        $this->setDefinition($this->originalCommand->getDefinition());
    }

    /**
     * Executes the original command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->originalCommand->execute($input, $output);
    }

    /**
     * Runs the original command first, then executes all registered chain member commands.
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info(sprintf('%s is a parent command of a command chain that has registered member commands', $this->parentCommandName));
        $memberServiceIds = $this->manager->getChainMembers($this->parentServiceId);

        // logging members
        foreach ($memberServiceIds as $memberServiceId) {
            $memberCommand = $this->container->get($memberServiceId);
            if ($memberCommand instanceof Command) {
                $this->logger->info(sprintf('%s registered as a member of %s command chain', $memberCommand->getName(), $this->parentCommandName));
            }
        }

        // run parent command
        $this->logger->info(sprintf('Executing %s command itself first:', $this->parentCommandName));
        $statusCode = $this->originalCommand->run(new ArrayInput([]), $output);

        if (Command::SUCCESS !== $statusCode) {
            $this->logger->warning(sprintf('Execution of %s chain skipped because the parent command failed with status code %d.', $this->parentCommandName, $statusCode));

            return $statusCode;
        }

        if (empty($memberServiceIds)) {
            $this->logger->info(sprintf('Command %s has no chain members to execute', $this->parentCommandName));

            return $statusCode;
        }

        // executing chain members
        $this->logger->info(sprintf('Executing %s chain members:', $this->parentCommandName));
        foreach ($memberServiceIds as $memberServiceId) {
            try {
                $memberCommand = $this->container->get($memberServiceId);
                $memberCommand->run(new ArrayInput([]), $output);
            } catch (Throwable $e) {
                $this->logger->error(sprintf('Exception thrown while running chain member "%s": %s', $memberServiceId, $e->getMessage()));
                $statusCode = Command::FAILURE;
            }
        }

        $this->logger->info(sprintf('Execution of %s chain completed.', $this->parentCommandName));

        return $statusCode;
    }
}
