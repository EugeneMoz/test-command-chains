<?php

declare(strict_types=1);

namespace EugeneMoz\ChainCommandBundle\Tests\Functional\app;

use EugeneMoz\ChainCommandBundle\ChainCommandBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new ChainCommandBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config.yaml');
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/logs';
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }
}
