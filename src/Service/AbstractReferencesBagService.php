<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Symfony\Contracts\Service\ResetInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyHelpers\Entity\Interfaces\AbstractEntityInterface;
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
     * The name of each registered entity, by class key then by real class and
     * id, read off the registry as it grows: the name of an entity would
     * otherwise be looked for by comparing it with every entity of its class,
     * once per entity exported. `count` is how many entries were read.
     *
     * @var array<string, array{count: int, names: array<string, string>}>
     */
    private array $referenceNames = [];

    /**
     * Clears all registered references.
     */
    public function reset(): void
    {
        $this->registry = [];
        $this->referenceNames = [];
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

        if ($object instanceof AbstractEntityInterface) {
            $refName = $this->indexReferenceNames($classKey)[$this->getEntityIdentity($object)] ?? null;

            // Not in the index, which holds every entity registered: no name.
            if (null === $refName) {
                return null;
            }

            // Still the same entity under that name, unless the registry was
            // written to without addReference(): then it is looked for in full.
            if (isset($this->registry[$classKey][$refName])
                && EntityHelper::areSame($this->registry[$classKey][$refName], $object)) {
                return $refName;
            }
        }

        foreach ($this->registry[$classKey] as $refName => $obj) {
            if (EntityHelper::areSame($obj, $object)) {
                return $refName;
            }
        }

        return null;
    }

    /**
     * The first name of each entity registered under the class key, by
     * identity: the entries added since the last call are read, the whole
     * registry again when it shrank — emptied, or rewritten.
     *
     * @return array<string, string>
     */
    private function indexReferenceNames(string $classKey): array
    {
        $references = $this->registry[$classKey];
        $index = $this->referenceNames[$classKey] ?? null;

        if (null === $index || $index['count'] > count($references)) {
            $index = ['count' => 0, 'names' => []];
        }

        foreach (array_slice($references, $index['count'], null, true) as $refName => $reference) {
            if ($reference instanceof AbstractEntityInterface) {
                $index['names'][$this->getEntityIdentity($reference)] ??= $refName;
            }
        }

        $index['count'] = count($references);
        $this->referenceNames[$classKey] = $index;

        return $index['names'];
    }

    /**
     * What EntityHelper::areSame() compares: the real class, and the id.
     */
    private function getEntityIdentity(AbstractEntityInterface $entity): string
    {
        return ClassHelper::getRealClassPath($entity) . '#' . $entity->getId()->toRfc4122();
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
