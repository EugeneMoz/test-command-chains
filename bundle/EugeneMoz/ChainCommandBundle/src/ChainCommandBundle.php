<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle;

use EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler\ChainCommandCompilerPass;
use EugeneMoz\ChainCommandBundle\DependencyInjection\Compiler\ChainCommandDecoratorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Chain Command Bundle allows creating command chains where one command can trigger other commands.
 */
class ChainCommandBundle extends Bundle
{
    /**
     * Builds the bundle by registering compiler passes.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Default priority is 0. This pass must be run before the decorator pass.
        $container->addCompilerPass(new ChainCommandCompilerPass());

        // This pass decorates the parent commands.
        $container->addCompilerPass(new ChainCommandDecoratorCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
    }
}
