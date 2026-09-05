<?php
namespace App\Models\Reports;
use Illuminate\Database\Eloquent\Model;
class ReportSavedView extends Model
{
    protected $guarded=[];
    protected $casts=[
        'filters_json'=>'array','columns_json'=>'array','sort_json'=>'array',
        'group_json'=>'array','chart_json'=>'array','is_default'=>'boolean',
    ];
    public function report(){return $this->belongsTo(ReportDefinition::class,'report_definition_id');}
    public function user(){return $this->belongsTo(\App\Models\User::class);}
}
