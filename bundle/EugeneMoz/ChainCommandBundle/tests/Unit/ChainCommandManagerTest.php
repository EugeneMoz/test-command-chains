<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Unit;

use EugeneMoz\ChainCommandBundle\Service\ChainCommandManager;
use PHPUnit\Framework\TestCase;

class ChainCommandManagerTest extends TestCase
{
    /**
     * Test adding chain members.
     */
    public function testAddChainMember(): void
    {
        $manager = new ChainCommandManager();

        $manager->addChainMember('test.parent', 'test.member');

        $this->assertTrue(
            $manager->isChainMember('test.member'),
            'Child command should be part of the chain'
        );

        $this->assertEquals(
            'test.parent',
            $manager->getParentForMember('test.member'),
            'Incorrect parent for child command'
        );

        $this->assertEquals(
            ['test.member'],
            $manager->getChainMembers('test.parent'),
            'Incorrect child commands for parent'
        );
    }

    /**
     * Test class mapping.
     */
    public function testClassMapping(): void
    {
        $manager = new ChainCommandManager();

        $manager->addMemberClassMapping('TestClass', 'test.service.id');

        $this->assertEquals(
            'test.service.id',
            $manager->getServiceIdByClassName('TestClass'),
            'Incorrect service ID for class'
        );
    }

    /**
     * Negative test: requesting non-existent parent.
     */
    public function testGetNonExistentParent(): void
    {
        $manager = new ChainCommandManager();

        $this->assertNull(
            $manager->getParentForMember('non_existent'),
            'Method should return null for non-existent child command'
        );
    }

    /**
     * Negative test: requesting non-existent chain members.
     */
    public function testGetNonExistentChainMembers(): void
    {
        $manager = new ChainCommandManager();

        $this->assertEmpty(
            $manager->getChainMembers('non_existent'),
            'Method should return empty array for non-existent parent command'
        );
    }

    /**
     * Negative test: checking non-existent service by class.
     */
    public function testGetNonExistentServiceIdByClassName(): void
    {
        $manager = new ChainCommandManager();

        $this->assertNull(
            $manager->getServiceIdByClassName('NonExistentClass'),
            'Method should return null for non-existent class'
        );
    }

    /**
     * Test checking status of command that is not a chain member.
     */
    public function testIsNotChainMember(): void
    {
        $manager = new ChainCommandManager();

        $this->assertFalse(
            $manager->isChainMember('standalone_command'),
            'Method should return false for command that is not a chain member'
        );
    }
}
