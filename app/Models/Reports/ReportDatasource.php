<?php
namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class ReportDatasource extends Model
{
    protected $guarded=[];
    protected $casts=['is_active'=>'boolean'];
    public function fields(){return $this->hasMany(ReportField::class)->orderBy('sort_order')->orderBy('label');}
}
