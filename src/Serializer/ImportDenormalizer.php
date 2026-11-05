<?php

namespace Wexample\SymfonyEntityTransfer\Serializer;

use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Exception\PropertyTypeException;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizer for import DTOs that handles mixed type properties
 * (string|DTO|null) to properly manage references and nested objects.
 */
class ImportDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfo
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): mixed {
        $object = new $type();

        foreach ($data as $property => $value) {
            if (! property_exists($object, $property)) {
                continue;
            }

            $propertyType = $this->unwrap($this->propertyInfo->getType($type, $property));

            if (is_array($value) && [] !== $value) {
                // A collection of DTOs: each item denormalized as its class.
                if ($propertyType instanceof CollectionType
                    && ($itemType = $this->unwrap($propertyType->getCollectionValueType())) instanceof ObjectType) {
                    $this->assignProperty(
                        $object,
                        $property,
                        $this->convertCollection($value, $itemType->getClassName(), $format, $context),
                        'array'
                    );

                    continue;
                }

                // A nested DTO.
                if ($propertyType instanceof ObjectType) {
                    $this->assignProperty(
                        $object,
                        $property,
                        $this->denormalizer->denormalize($value, $propertyType->getClassName(), $format, $context),
                        $propertyType->getClassName()
                    );

                    continue;
                }
            }

            $this->assignProperty($object, $property, $value, (string) ($propertyType ?? 'mixed'));
        }

        return $object;
    }

    /**
     * The type a nullable one wraps: `?Shelf` is read as `Shelf`.
     */
    private function unwrap(?Type $type): ?Type
    {
        return $type instanceof NullableType ? $type->getWrappedType() : $type;
    }

    private function convertCollection(
        array $items,
        string $itemClass,
        ?string $format,
        array $context
    ): array {
        $convertedItems = [];
        foreach ($items as $key => $item) {
            $convertedItems[$key] = is_array($item)
                ? $this->denormalizer->denormalize($item, $itemClass, $format, $context)
                : $item;
        }

        return $convertedItems;
    }

    private function assignProperty(
        object $object,
        string $property,
        mixed $value,
        string $expectedType
    ): void {
        try {
            $object->$property = $value;
        } catch (\TypeError $e) {
            throw new PropertyTypeException(
                $property,
                $expectedType,
                gettype($value),
                previous: $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_subclass_of($type, AbstractImportValidationDto::class);
    }


    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => true,
        ];
    }
}
