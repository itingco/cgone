<?php
namespace App\Models\Concerns; use DomainException;
trait ImmutableWhenPosted { public static function bootImmutableWhenPosted(): void { static::updating(function($model){ if($model->getOriginal('status')==='POSTED') throw new DomainException('Posted ledger rows are immutable. Use adjustment/reversal.'); }); static::deleting(function($model){ if($model->getOriginal('status')==='POSTED') throw new DomainException('Posted ledger rows are immutable. Use adjustment/reversal.'); }); } }
