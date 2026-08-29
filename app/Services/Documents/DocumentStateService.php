<?php
namespace App\Services\Documents;

use App\Domain\Documents\DocumentRules;
use App\Services\Audit\ActivityLogService;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class DocumentStateService
{
    public function __construct(private ActivityLogService $audit) {}

    public function release(Model $document, int $userId): Model { return $this->transition($document, 'RELEASED', $userId); }
    public function reopen(Model $document, int $userId): Model { return $this->transition($document, 'OPEN', $userId); }

    public function markPosted(Model $document, int $postedId, int $userId, ?string $postedNumber=null): Model
    {
        if (!DocumentRules::canTransition((string)$document->status, 'POSTED')) throw new DomainException('Only RELEASED documents can be posted.');
        $before=$document->toArray();
        $document->forceFill(['status'=>'POSTED','posted_document_id'=>$postedId,'posted_by'=>$userId,'posted_at'=>now()])->save();
        $this->audit->record('documents','post',$document,$before,$document->fresh()->toArray(),['document_number'=>$document->document_no,'posted_number'=>$postedNumber]);
        return $document->fresh();
    }

    public function markUndo(Model $document, int $userId): Model
    {
        if (!DocumentRules::canTransition((string)$document->status, 'UNDO')) throw new DomainException('Only POSTED documents can be undone.');
        $before=$document->toArray();
        $document->forceFill(['status'=>'UNDO','undone_by'=>$userId,'undone_at'=>now()])->save();
        $this->audit->record('documents','undo',$document,$before,$document->fresh()->toArray(),['document_number'=>$document->document_no]);
        return $document->fresh();
    }

    private function transition(Model $document, string $to, int $userId): Model
    {
        $from=strtoupper((string)$document->status);
        if (!DocumentRules::canTransition($from,$to)) throw new DomainException("Document cannot transition from {$from} to {$to}.");
        $before=$document->toArray();
        $values=['status'=>$to];
        if ($to==='RELEASED') $values+=['released_by'=>$userId,'released_at'=>now()];
        if ($to==='OPEN') $values+=['released_by'=>null,'released_at'=>null];
        $document->forceFill($values)->save();
        $this->audit->record('documents',strtolower($to),$document,$before,$document->fresh()->toArray(),['document_number'=>$document->document_no]);
        return $document->fresh();
    }
}
