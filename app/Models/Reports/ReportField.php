<?php
namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class ReportField extends Model
{
    protected $guarded=[];
    protected $casts=[
        'is_numeric'=>'boolean','aggregate_allowed'=>'boolean','filter_allowed'=>'boolean',
        'group_allowed'=>'boolean','sort_allowed'=>'boolean','metadata_json'=>'array',
    ];
    public function datasource(){return $this->belongsTo(ReportDatasource::class,'report_datasource_id');}
}
