<?php
namespace App\Models\Reports;
use Illuminate\Database\Eloquent\Model;
class ReportRoleAccess extends Model
{
    protected $table='report_role_access';
    protected $guarded=[];
    protected $casts=[
        'can_view'=>'boolean','can_export'=>'boolean','can_print'=>'boolean','can_edit'=>'boolean',
        'can_share'=>'boolean','can_clone'=>'boolean','can_delete'=>'boolean','can_manage'=>'boolean',
    ];
    public function report(){return $this->belongsTo(ReportDefinition::class,'report_definition_id');}
    public function role(){return $this->belongsTo(\App\Models\Role::class);}
}
