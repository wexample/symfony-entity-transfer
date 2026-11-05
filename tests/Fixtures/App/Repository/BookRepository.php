<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository;

use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

class BookRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return Book::class;
    }
}
