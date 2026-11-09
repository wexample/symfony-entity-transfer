<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Symfony\Contracts\Service\ResetInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyHelpers\Helper\EntityHelper;

/**
 * The references of one import or export — what a file names `_ref`, and
 * the objects they stand for —, emptied between two of them and between two
 * requests a worker serves.
 */
abstract class AbstractReferencesBagService implements ResetInterface
{
    /**
     * @var array<string, array<string, object>>
     */
    protected array $registry = [];

    /**
     * Clears all registered references.
     */
    public function reset(): void
    {
        $this->registry = [];
    }

    /**
     * Normalize an entity class or type to its registry key.
     */
    protected function getClassKey(string|object $entityClass): string
    {
        return ClassHelper::getTableizedName($entityClass);
    }

    /**
     * Core logic to register a reference. Throws if duplicate.
     */
    public function addReference(
        object $object,
        string $referenceName
    ): string {
        $classKey = $this->getClassKey($this->getEntityClassName($object));
        if (! isset($this->registry[$classKey])) {
            $this->registry[$classKey] = [];
        }
        if (isset($this->registry[$classKey][$referenceName])) {
            throw $this->createNonUniqueReferenceException(
                $referenceName,
                $this->getEntityClassName($object)
            );
        }
        $this->registry[$classKey][$referenceName] = $object;

        return $referenceName;
    }

    /**
     * Must return the entity's class name (import DTO or real entity).
     */
    abstract protected function getEntityClassName(object $object): string;

    /**
     * Must create and return the appropriate exception for duplicates.
     */
    abstract protected function createNonUniqueReferenceException(
        string $referenceName,
        string $entityClass
    ): \Exception;

    /**
     * Retrieves a single reference by name.
     */
    public function getReference(
        string $referenceName,
        string|object $entityClass
    ): ?object {
        $classKey = $this->getClassKey($entityClass);

        return $this->registry[$classKey][$referenceName] ?? null;
    }

    /**
     * Retrieves all references for a given entity type.
     * @return array<string, object>
     */
    public function getReferencesForEntityType(string|object $entityClass): array
    {
        $classKey = $this->getClassKey($entityClass);

        return $this->registry[$classKey] ?? [];
    }

    /**
     * Returns a flat list of all "type:refName" values.
     * @return string[]
     */
    public function listReferences(): array
    {
        $output = [];
        foreach ($this->registry as $entityKey => $refs) {
            foreach ($refs as $refName => $_) {
                $output[] = "{$entityKey}:{$refName}";
            }
        }

        return $output;
    }

    /**
     * Finds a reference via a full "type:refName" string.
     * @param string $fullReference
     * @return object|null
     */
    public function getByFullReference(string $fullReference): ?object
    {
        [$type, $ref] = ReferenceHelper::parseEntityReference($fullReference);
        if (! $ref) {
            return null;
        }

        return $this->getReference($ref, $type);
    }

    /**
     * Retrieves the reference name for a given object instance.
     * @param object $object
     * @return string|null
     */
    public function getReferenceName(object $object): ?string
    {
        $classKey = $this->getClassKey($this->getEntityClassName($object));
        if (! isset($this->registry[$classKey])) {
            return null;
        }
        foreach ($this->registry[$classKey] as $refName => $obj) {
            if (EntityHelper::areSame($obj, $object)) {
                return $refName;
            }
        }

        return null;
    }

    public function addReferenceOnce(
        object $object,
        string $referenceName
    ): string {
        if (! $this->getReference($referenceName, $object)) {
            return $this->addReference(
                $object,
                $referenceName
            );
        }

        return $referenceName;
    }
}
