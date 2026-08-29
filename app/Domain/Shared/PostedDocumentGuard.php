<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Shared\Exceptions\PostedDocumentImmutableException;

final class PostedDocumentGuard
{
    public function assertMutable(DocumentStatus|string $status): void
    {
        $value = $status instanceof DocumentStatus ? $status->value : $status;

        if (in_array($value, [DocumentStatus::POSTED->value, DocumentStatus::REVERSED->value], true)) {
            throw new PostedDocumentImmutableException(
                'Dokumen yang sudah diposting tidak dapat diedit atau dihapus. Gunakan adjustment atau reversal.'
            );
        }
    }
}
