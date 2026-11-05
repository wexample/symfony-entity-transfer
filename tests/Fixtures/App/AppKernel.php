<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App;

use Wexample\SymfonyEntityTransfer\WexampleSymfonyEntityTransferBundle;
use Wexample\SymfonyTesting\Tests\Fixtures\AbstractFixtureKernel;

class AppKernel extends AbstractFixtureKernel
{
    protected function getFixtureDir(): string
    {
        return __DIR__;
    }

    protected function getExtraBundles(): iterable
    {
        return [new WexampleSymfonyEntityTransferBundle()];
    }

    protected function getConfigFiles(): array
    {
        return [__DIR__ . '/config/config.yaml'];
    }
}
