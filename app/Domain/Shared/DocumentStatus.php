<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case POSTED = 'posted';
    case REVERSED = 'reversed';
    case REJECTED = 'rejected';
}
