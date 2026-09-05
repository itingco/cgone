<?php
namespace App\Models\Reports;
use Illuminate\Database\Eloquent\Model;
class ReportFavorite extends Model
{
    protected $guarded=[];
    public function report(){return $this->belongsTo(ReportDefinition::class,'report_definition_id');}
    public function user(){return $this->belongsTo(\App\Models\User::class);}
}
