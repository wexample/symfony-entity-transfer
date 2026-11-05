<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Exception\ImportExportNonUniqueReferenceException;

/**
 * Service managing references for import operations.
 */
class ImportReferencesBagService extends AbstractReferencesBagService
{
    /**
     * @param string $referenceName
     * @param string|object $entityClass
     * @return AbstractEntityImportDto|null
     */
    public function getImportReference(
        string $referenceName,
        string|object $entityClass
    ): ?AbstractEntityImportDto {
        return $this->getReference($referenceName, $entityClass);
    }

    /**
     * @return string[]
     */
    public function listImportReferences(): array
    {
        return $this->listReferences();
    }

    /**
     * @param string $fullReference
     * @return AbstractEntityImportDto|null
     */
    public function getImportByFullReference(string $fullReference): ?AbstractEntityImportDto
    {
        return $this->getByFullReference($fullReference);
    }

    /**
     * @param object $object
     * @return string|null
     */
    public function getImportReferenceName(object $object): ?string
    {
        return $this->getReferenceName($object);
    }

    protected function getEntityClassName(object $object): string
    {
        return $object::getEntityClassName();
    }

    protected function createNonUniqueReferenceException(
        string $referenceName,
        string $entityClass
    ): \Exception {
        return new ImportExportNonUniqueReferenceException(
            $referenceName,
            $entityClass
        );
    }
}
