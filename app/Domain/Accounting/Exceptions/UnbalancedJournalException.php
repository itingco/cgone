<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Exceptions;

use DomainException;

final class UnbalancedJournalException extends DomainException
{
}
