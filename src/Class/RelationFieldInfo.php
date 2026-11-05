<?php

namespace Wexample\SymfonyEntityTransfer\Class;

/**
 * Class representing information about a relation field in a validation DTO
 */
class RelationFieldInfo
{
    /**
     * @param string $fieldType The type of relation field (id, ref, unique, dto)
     * @param string $fieldName The name of the field in the DTO
     * @param mixed $value The value of the field
     */
    public function __construct(
        public readonly string $fieldType,
        public readonly string $fieldName,
        public readonly mixed $value
    ) {
    }
}
