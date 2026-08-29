<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Exceptions;

use DomainException;

final class BackdatedPriceException extends DomainException
{
}
