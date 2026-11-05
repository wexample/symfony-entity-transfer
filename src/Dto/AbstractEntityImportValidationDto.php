<?php

namespace Wexample\SymfonyEntityTransfer\Dto;

use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Uuid;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;
use Wexample\SymfonyHelpers\Helper\VariableHelper;

abstract class AbstractEntityImportValidationDto extends AbstractImportValidationDto
{
    use EntityManipulatorTrait;

    #[Type(VariableHelper::VARIABLE_TYPE_STRING)]
    public string $_ref;

    /** @var int|null Allow to pass id when editing existing entity. */
    #[Type(VariableHelper::VARIABLE_TYPE_STRING)]
    #[Uuid]
    public ?string $id = null;

    /**
     * Check if this DTO has a specific sub-entity type
     *
     * @param string|AbstractEntity $entityClassName The entity class to check for
     * @return array<AbstractImportDto>
     */
    public function getRelatedDtoCollection(string|AbstractEntity $entityClassName): array
    {
        $entityKey = ClassHelper::getFieldName($entityClassName);

        // Check if the property exists and is not empty
        if (property_exists($this, $entityKey) && is_array($this->$entityKey)) {
            return $this->$entityKey;
        }

        return [];
    }
}
