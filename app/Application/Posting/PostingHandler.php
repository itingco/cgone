<?php

namespace App\Application\Posting;

interface PostingHandler
{
    /** @return array{document_id:int,journal_id?:int,inventory_transaction_id?:int} */
    public function post(int $documentId, int $userId): array;
}
