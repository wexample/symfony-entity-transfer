<?php

namespace Wexample\SymfonyEntityTransfer\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyEntityTransfer\Interface\FileUpcasterInterface;
use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportEntityService;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyEntityTransferExtension extends AbstractWexampleSymfonyExtension
{
    final public const string TAG_ENTITY_SERVICE = 'wexample.entity_transfer.entity_service';

    final public const string TAG_UPCASTER = 'wexample.entity_transfer.upcaster';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $container->registerForAutoconfiguration(AbstractImportExportEntityService::class)
            ->addTag(self::TAG_ENTITY_SERVICE);
        $container->registerForAutoconfiguration(FileUpcasterInterface::class)
            ->addTag(self::TAG_UPCASTER);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
