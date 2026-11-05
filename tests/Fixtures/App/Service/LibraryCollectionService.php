<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service;

use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportCollectionService;

class LibraryCollectionService extends AbstractImportExportCollectionService
{
    protected function getConnectReferencesOrder(): array
    {
        return [];
    }
}
