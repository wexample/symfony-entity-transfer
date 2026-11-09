<?php

namespace Wexample\SymfonyEntityTransfer\Normalizer;

use ArrayObject;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyEntityTransfer\Service\ExportReferencesBagService;
use Wexample\SymfonyEntityTransfer\Service\ImportExportServiceLocator;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Normalizer\AbstractLegacyEntityNormalizer;

/**
 * The export of an entity: its fields, and its relations as references —
 * `_ref`, or the entity's id with `asExisting`.
 */
abstract class AbstractExportNormalizer extends AbstractLegacyEntityNormalizer
{
    public function __construct(
        protected readonly ImportExportServiceLocator $importExportServiceLocator,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ExportReferencesBagService $referencesBag,
    ) {

    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|null|ArrayObject {
        $output = [];

        if ($context['asExisting'] ?? false) {
            $id = $object->getId();
            $ref = ReferenceHelper::buildReferenceId($object);

            $output['id'] = $id;
            $output[static::buildIdKey()] = $ref;
            $context[static::buildIdKey()] = $ref;
        }

        $output += parent::normalize(
            $object,
            $format,
            $context
        );

        return $output;
    }

    protected function buildIdKey(): string
    {
        return ReferenceHelper::REFERENCE_VALUE_KEY;
    }

    protected function buildIdValue(
        AbstractEntity $object,
        array $context = []
    ): string {
        // Register in exportation bag if not reference name provided.
        return $context[static::buildIdKey()] ?? $this->referencesBag->getOrCreateExportReferenceName(
            $object,
            asExisting: $context['asExisting'] ?? false
        );
    }

    protected function buildEntityReferencePair(
        AbstractEntity $entity,
        array $context,
        ?string $key = null,
        ?string $field = null
    ) {
        if (! $key) {
            $key = ReferenceHelper::buildReferenceKey($entity);
        }

        if ($field) {
            $key = $entity::getCamelName() . ReferenceHelper::FIELD_SEPARATOR . $field;
            $value = PropertyAccess::createPropertyAccessor()->getValue($entity, $field);
        } else {
            $value = $this->referencesBag->getOrCreateExportReferenceName(
                $entity,
                asExisting: $context['asExisting'] ?? false
            );
        }

        return [$key => $value];
    }


    protected function buildNormalizeChildPair(
        string|AbstractEntity $className,
        ?AbstractEntity $entity,
        array $context,
        ?string $key = null
    ): array {
        return [($key ?: $className::getCamelName()) => $this->normalizeChild($className, $entity, $context)];
    }

    protected function buildNormalizeChildCollectionPair(
        string|AbstractEntity $className,
        array|Collection $collection,
        array $context,
        ?string $key = null
    ): array {
        return [($key ?: $className::getCamelName()) => $this->normalizeChildEntitiesCollection($className, $collection, $context)];
    }

    protected function normalizeChild(
        string|AbstractEntity $className,
        ?AbstractEntity $entity,
        array $context
    ): array|string|int|float|bool|ArrayObject|null {
        return $entity ? $this
            ->importExportServiceLocator
            ->getService($className)
            ->getNormalizer()
            ->normalize(
                $entity,
                context: $context
            ) : null;
    }

    protected function normalizeChildEntitiesCollection(
        string|AbstractEntity $className,
        array|Collection $collection,
        array $context
    ): array|string|int|float|bool|ArrayObject|null {
        if (! ($context['exportChildren'] ?? false)) {
            return [];
        }

        return $this
            ->importExportServiceLocator
            ->getService($className)
            ->getNormalizer()
            ->normalizeEntitiesCollection(
                $collection,
                context: $context
            );
    }
}
