<?php
namespace App\Models\Reports;
use Illuminate\Database\Eloquent\Model;
class ReportExecutionLog extends Model
{
    protected $guarded=[];
    protected $casts=[
        'parameters_json'=>'array','filters_json'=>'array',
        'started_at'=>'datetime','finished_at'=>'datetime',
    ];
    public function report(){return $this->belongsTo(ReportDefinition::class,'report_definition_id');}
    public function user(){return $this->belongsTo(\App\Models\User::class);}
}
