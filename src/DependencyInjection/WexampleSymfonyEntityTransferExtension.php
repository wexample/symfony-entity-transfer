<?php

namespace Wexample\SymfonyEntityTransfer\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportEntityService;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyEntityTransferExtension extends AbstractWexampleSymfonyExtension
{
    final public const string TAG_ENTITY_SERVICE = 'wexample.entity_transfer.entity_service';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $container->registerForAutoconfiguration(AbstractImportExportEntityService::class)
            ->addTag(self::TAG_ENTITY_SERVICE);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
