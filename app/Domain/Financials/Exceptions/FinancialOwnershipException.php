<?php

declare(strict_types=1);

namespace App\Domain\Financials\Exceptions;

use RuntimeException;

final class FinancialOwnershipException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You do not own the selected financial resource.');
    }
}
