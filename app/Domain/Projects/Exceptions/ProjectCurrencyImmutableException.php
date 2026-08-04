<?php

declare(strict_types=1);

namespace App\Domain\Projects\Exceptions;

use DomainException;

final class ProjectCurrencyImmutableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The project currency cannot change after financial activity begins.');
    }
}
