<?php

namespace Wexample\SymfonyEntityTransfer\Helper;

use Wexample\SymfonyEntityTransfer\Class\RelationFieldInfo;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Interface\ImportableEntityInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\Helpers\Helper\TextHelper;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * Helper class for handling entity relation fields in import DTOs
 */
class ReferenceHelper
{
    /**
     * Field separator used in relation field names
     */
    public const string FIELD_SEPARATOR = '_';
    public const string ENTITY_REFERENCE_SEPARATOR = ':';
    // _ref
    public const string REFERENCE_VALUE_KEY = '_' . self::SUFFIX_REF;
    // _ref|
    public const string REFERENCE_VALUE_PREFIX = self::REFERENCE_VALUE_KEY . '|';

    /**
     * Relation field suffixes
     */
    public const string SUFFIX_ID = 'id';
    public const string SUFFIX_REF = 'ref';

    /**
     * Relation field types
     */
    public const string RELATION_TYPE_ID = 'id';
    public const string RELATION_TYPE_REF = 'ref';
    public const string RELATION_TYPE_UNIQUE = 'unique';
    public const string RELATION_TYPE_DTO = 'dto';

    /**
     * Get all possible relation field names for a target entity class
     *
     * @param string|ImportableEntityInterface|AbstractEntity $targetEntityClass The target entity class
     * @param string|null $relationEntityFieldName
     * @return array The list of possible relation field names
     */
    public static function getRelationFieldNames(
        string $targetEntityClass,
        ?string $relationEntityFieldName = null
    ): array {
        $fieldNames = [];
        $relationEntityFieldName = $relationEntityFieldName ?: ClassHelper::getFieldName($targetEntityClass);

        // Direct ID reference (e.g., job_id)
        $fieldNames[self::RELATION_TYPE_ID] = $relationEntityFieldName . self::FIELD_SEPARATOR . self::SUFFIX_ID;

        // Reference by symbolic reference (e.g., job_ref)
        $fieldNames[self::RELATION_TYPE_REF] = $relationEntityFieldName . self::FIELD_SEPARATOR . self::SUFFIX_REF;

        // Reference by unique field (e.g., job_title)
        if ($uniqueFieldName = $targetEntityClass::getEntityImportDtoClass()::getUniqueFieldName()) {
            $fieldNames[self::RELATION_TYPE_UNIQUE] = $relationEntityFieldName . self::FIELD_SEPARATOR . $uniqueFieldName;
        }

        // Direct DTO reference (e.g., job)
        $fieldNames[self::RELATION_TYPE_DTO] = $relationEntityFieldName;

        return $fieldNames;
    }

    /**
     * Check if a validation DTO has at least one valid relation field for a target entity
     *
     * @param AbstractImportValidationDto $validationDto The validation DTO
     * @param string|ImportableEntityInterface|AbstractEntity $targetEntityClass The target entity class
     * @param string|null $relationEntityFieldName
     * @return bool True if at least one relation field is set, false otherwise
     */
    public static function hasRelationField(
        AbstractImportValidationDto $validationDto,
        string $targetEntityClass,
        ?string $relationEntityFieldName = null
    ): bool {
        return self::findRelationValue(
            $validationDto,
            $targetEntityClass,
            $relationEntityFieldName,
        ) !== null;
    }

    /**
     * Find the relation value in a validation DTO
     *
     * @param AbstractImportValidationDto $validationDto The validation DTO
     * @param string|ImportableEntityInterface|AbstractEntity $targetEntityClass The target entity class
     * @param string|null $relationEntityFieldName
     * @return RelationFieldInfo|null An object with relation field information, or null if no relation is found
     */
    public static function findRelationValue(
        AbstractImportValidationDto $validationDto,
        string $targetEntityClass,
        ?string $relationEntityFieldName = null
    ): ?RelationFieldInfo {
        $fieldNames = self::getRelationFieldNames(
            $targetEntityClass,
            $relationEntityFieldName,
        );

        // Check ID reference
        $idField = $fieldNames[self::RELATION_TYPE_ID];
        if (property_exists($validationDto, $idField) && isset($validationDto->$idField)) {
            return new RelationFieldInfo(
                fieldType: self::RELATION_TYPE_ID,
                fieldName: $idField,
                value: $validationDto->$idField
            );
        }

        // Check unique field reference
        if ($uniqueField = ($fieldNames[self::RELATION_TYPE_UNIQUE] ?? null)) {
            if (property_exists($validationDto, $uniqueField) && isset($validationDto->$uniqueField)) {
                return new RelationFieldInfo(
                    fieldType: self::RELATION_TYPE_UNIQUE,
                    fieldName: $uniqueField,
                    value: $validationDto->$uniqueField
                );
            }
        }

        // Check symbolic reference
        $refField = $fieldNames[self::RELATION_TYPE_REF];
        if (property_exists($validationDto, $refField) && isset($validationDto->$refField)) {
            return new RelationFieldInfo(
                fieldType: self::RELATION_TYPE_REF,
                fieldName: $refField,
                value: $validationDto->$refField
            );
        }

        // Check direct DTO reference
        $dtoField = $fieldNames[self::RELATION_TYPE_DTO];
        if (property_exists($validationDto, $dtoField) && isset($validationDto->$dtoField)) {
            if ($validationDto->$dtoField instanceof AbstractEntityImportValidationDto) {
                return new RelationFieldInfo(
                    fieldType: self::RELATION_TYPE_DTO,
                    fieldName: $dtoField,
                    value: $validationDto->$dtoField
                );
            }
        }

        return null;
    }

    /**
     * Get all available relation fields in a validation DTO
     *
     * @param AbstractImportValidationDto $validationDto The validation DTO
     * @param string $targetEntityClass The target entity class
     * @param string|null $relationEntityFieldName
     * @return array The list of available relation field names
     */
    public static function getAvailableRelationFields(
        AbstractImportValidationDto $validationDto,
        string $targetEntityClass,
        ?string $relationEntityFieldName = null
    ): array {
        $fieldNames = self::getRelationFieldNames(
            $targetEntityClass,
            $relationEntityFieldName
        );

        $availableFields = [];

        foreach ($fieldNames as $fieldName) {
            if (property_exists($validationDto, $fieldName)) {
                $availableFields[] = $fieldName;
            }
        }

        return $availableFields;
    }

    public static function parseEntityReference(
        string $reference
    ): ?array {
        if (! str_contains($reference, self::ENTITY_REFERENCE_SEPARATOR)) {
            return null;
        }

        [$entityKeyName, $referenceCode] = explode(
            self::ENTITY_REFERENCE_SEPARATOR,
            $reference,
            2
        );

        if (empty($entityKeyName) || empty($referenceCode)) {
            return null;
        }

        return [$entityKeyName, $referenceCode];
    }

    public static function buildReferenceKey(
        string|AbstractEntity $entityClass
    ): string {
        return $entityClass::getCamelName() . self::REFERENCE_VALUE_KEY;
    }

    public static function buildReferenceId(
        ?AbstractEntity $entityClass = null
    ): string {
        return $entityClass
            ? ('existing-' . ClassHelper::getTableizedName($entityClass) . '-' . $entityClass->getId())
            : uniqid();
    }

    /**
     * Remove *_ref keys and drop base keys when a sibling *_ref exists.
     *
     * @param array $importExportData
     * @return array
     */
    public static function removeReferences(
        array $importExportData
    ): array {
        $output = [];
        $keys = array_keys($importExportData);
        $hasRefForKey = array_fill_keys($keys, false);

        foreach ($keys as $key) {
            // Track base keys that have a companion *_ref key at the same level.
            $baseKey = TextHelper::removeSuffix($key, self::REFERENCE_VALUE_KEY);
            if ($baseKey !== $key && $baseKey !== '') {
                $hasRefForKey[$baseKey] = true;
            }
        }

        foreach ($importExportData as $key => $value) {
            // Skip reference keys and also skip base keys when a *_ref sibling exists.
            if (TextHelper::removeSuffix($key, self::REFERENCE_VALUE_KEY) === $key) {
                if ($hasRefForKey[$key] ?? false) {
                    continue;
                }
                $output[$key] = $value;

                if (is_array($value)) {
                    $output[$key] = self::removeReferences($value);
                }
            }
        }

        return $output;
    }
}
