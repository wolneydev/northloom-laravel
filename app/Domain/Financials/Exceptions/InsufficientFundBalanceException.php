<?php

declare(strict_types=1);

namespace App\Domain\Financials\Exceptions;

use RuntimeException;

final class InsufficientFundBalanceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected fund has insufficient available balance.');
    }
}
