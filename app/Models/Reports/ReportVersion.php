<?php
namespace App\Models\Reports;
use Illuminate\Database\Eloquent\Model;
class ReportVersion extends Model
{
    protected $guarded=[];
    protected $casts=['definition_snapshot_json'=>'array','changed_at'=>'datetime'];
    public function report(){return $this->belongsTo(ReportDefinition::class,'report_definition_id');}
    public function changedBy(){return $this->belongsTo(\App\Models\User::class,'changed_by');}
}
