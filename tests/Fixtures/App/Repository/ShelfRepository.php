<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository;

use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

class ShelfRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return Shelf::class;
    }
}
