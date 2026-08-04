<?php

declare(strict_types=1);

namespace App\Domain\Financials\Exceptions;

use DomainException;

final class ProjectCurrencyNotConfiguredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Configure the project currency before creating financial records.');
    }
}
