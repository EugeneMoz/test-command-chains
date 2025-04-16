<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Service;

class ChainCommandManager
{
    private array $chainMap = [];
    private array $memberToParentMap = [];
    private array $memberClassToServiceIdMap = [];

    /**
     * Adds a command as a member of a parent command's chain.
     * Called by ChainCommandCompilerPass.
     */
    public function addChainMember(string $parentServiceId, string $memberServiceId): void
    {
        // add parent to chain
        if (!isset($this->chainMap[$parentServiceId])) {
            $this->chainMap[$parentServiceId] = [];
        }
        // add member to chain
        $this->chainMap[$parentServiceId][] = $memberServiceId;

        // save member to parent direction
        $this->memberToParentMap[$memberServiceId] = $parentServiceId;
    }

    /**
     * Adds a mapping from a member command class name to its service ID.
     * Called by ChainCommandCompilerPass.
     */
    public function addMemberClassMapping(string $className, string $serviceId): void
    {
        $this->memberClassToServiceIdMap[$className] = $serviceId;
    }

    /**
     * Checks if a command is part of a chain.
     */
    public function isChainMember(string $serviceId): bool
    {
        return isset($this->memberToParentMap[$serviceId]);
    }

    /**
     * Get parent for member.
     */
    public function getParentForMember(string $memberServiceId): ?string
    {
        return $this->memberToParentMap[$memberServiceId] ?? null;
    }

    /**
     * Get members of a chain by parent.
     */
    public function getChainMembers(string $parentServiceId): array
    {
        return $this->chainMap[$parentServiceId] ?? [];
    }

    /**
     * Finds the service ID for a member command by its class name.
     */
    public function getServiceIdByClassName(string $className): ?string
    {
        return $this->memberClassToServiceIdMap[$className] ?? null;
    }
}
