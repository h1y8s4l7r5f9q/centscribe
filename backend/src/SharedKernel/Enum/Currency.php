<?php

declare(strict_types=1);

namespace App\SharedKernel\Enum;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case RUB = 'RUB';
}
