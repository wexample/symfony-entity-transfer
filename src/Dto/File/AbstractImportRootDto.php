<?php

namespace Wexample\SymfonyEntityTransfer\Dto\File;

use Wexample\SymfonyEntityTransfer\Dto\AbstractImportDto;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Choice;
use Wexample\SymfonyApi\Api\Attribute\RequiredDtoProperty;
use Wexample\SymfonyHelpers\Helper\VariableHelper;

class AbstractImportRootDto extends AbstractImportDto
{
    public const string TYPE_SINGLE = 'single';
    public const string TYPE_COLLECTION = 'collection';

    #[Assert\Type(VariableHelper::VARIABLE_TYPE_STRING)]
    #[RequiredDtoProperty]
    public string $version;

    #[Assert\Type(VariableHelper::VARIABLE_TYPE_STRING)]
    #[Choice(choices: [self::TYPE_SINGLE, self::TYPE_COLLECTION])]
    #[RequiredDtoProperty]
    public string $type;

    #[Assert\Type(VariableHelper::VARIABLE_TYPE_STRING)]
    public ?string $entity = null;

    #[Assert\Type(VariableHelper::VARIABLE_TYPE_ARRAY)]
    public array $test = [];
}
