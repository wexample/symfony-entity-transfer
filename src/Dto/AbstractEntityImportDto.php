<?php

namespace Wexample\SymfonyEntityTransfer\Dto;

use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;

/**
 * Abstract base class for all import DTOs
 * Provides common functionality for import operations
 */
abstract class AbstractEntityImportDto extends AbstractImportDto
{
    use EntityManipulatorTrait;

    public ?string $_ref = null;
    private ?AbstractEntity $entity = null;

    public function setEntity(AbstractEntity $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntity(): ?AbstractEntity
    {
        return $this->entity;
    }

    /**
     * Get the field name to use for uniqueness check
     */
    public static function getUniqueFieldName(): ?string
    {
        return null;
    }
}
