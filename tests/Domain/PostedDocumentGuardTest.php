<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Shared\DocumentStatus;
use App\Domain\Shared\Exceptions\PostedDocumentImmutableException;
use App\Domain\Shared\PostedDocumentGuard;
use Tests\Support\TestCase;

final class PostedDocumentGuardTest extends TestCase
{
    public function testDraftDocumentCanBeChanged(): void
    {
        (new PostedDocumentGuard())->assertMutable(DocumentStatus::DRAFT);
        $this->assertTrue(true);
    }

    public function testPostedDocumentCannotBeChanged(): void
    {
        $this->assertThrows(PostedDocumentImmutableException::class, fn () =>
            (new PostedDocumentGuard())->assertMutable(DocumentStatus::POSTED)
        );
    }
}
