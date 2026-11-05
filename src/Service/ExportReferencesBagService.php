<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Wexample\SymfonyEntityTransfer\Exception\ImportExportNonUniqueReferenceException;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * Service managing references for export operations.
 */
class ExportReferencesBagService extends AbstractReferencesBagService
{
    /**
     * @param string $referenceName
     * @param string|object $entityClass
     * @return AbstractEntity|null
     */
    public function getExportReference(
        string $referenceName,
        string|object $entityClass
    ): ?AbstractEntity {
        return $this->getReference($referenceName, $entityClass);
    }

    /**
     * @return string[]
     */
    public function listExportReferences(): array
    {
        return $this->listReferences();
    }

    /**
     * @param string $fullReference
     * @return AbstractEntity|null
     */
    public function getExportByFullReference(string $fullReference): ?AbstractEntity
    {
        return $this->getByFullReference($fullReference);
    }

    /**
     * @param object $object
     * @return string|null
     */
    public function getExportReferenceName(object $object): ?string
    {
        return $this->getReferenceName($object);
    }

    protected function getEntityClassName(object $object): string
    {
        return $object::class;
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

    public function getOrCreateExportReferenceName(
        AbstractEntity $entity,
        bool $asExisting
    ): string {
        // Existing ids are stable, so we can recreate it, it will be the same.
        if (! $asExisting && $savedName = $this->getReferenceName($entity)) {
            return $savedName;
        }

        return $this->addReferenceOnce(
            $entity,
            referenceName: ReferenceHelper::buildReferenceId($asExisting ? $entity : null)
        );
    }
}
