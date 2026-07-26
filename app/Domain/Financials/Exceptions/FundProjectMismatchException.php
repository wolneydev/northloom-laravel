<?php

declare(strict_types=1);

namespace App\Domain\Financials\Exceptions;

use RuntimeException;

final class FundProjectMismatchException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected fund does not belong to the task project.');
    }
}
