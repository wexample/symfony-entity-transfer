<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Normalizer;

use ArrayObject;
use Wexample\SymfonyEntityTransfer\Normalizer\AbstractExportNormalizer;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;

class ExportShelfNormalizer extends AbstractExportNormalizer
{
    public static function getEntityClassName(): string
    {
        return Shelf::class;
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return parent::normalize($object, $format, $context) + [
            'name' => $object->name,
            'title' => $object->title,
        ] + $this->buildNormalizeChildCollectionPair(Book::class, $object->books, $context + ['inShelf' => true]);
    }
}
