<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Normalizer;

use ArrayObject;
use Wexample\SymfonyEntityTransfer\Normalizer\AbstractExportNormalizer;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;

class ExportBookNormalizer extends AbstractExportNormalizer
{
    public static function getEntityClassName(): string
    {
        return Book::class;
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return parent::normalize($object, $format, $context) + [
            'title' => $object->title,
            'code' => $object->code,
        ] + (($context['inShelf'] ?? false) ? [] : $this->buildEntityReferencePair($object->shelf, $context, field: 'name'));
    }
}
