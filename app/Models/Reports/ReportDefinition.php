<?php
namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class ReportDefinition extends Model
{
    public const TYPE_STANDARD = 'STANDARD';
    public const TYPE_VISUAL = 'VISUAL';
    public const TYPE_SQL = 'SQL';

    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_SHARED = 'SHARED';
    public const VISIBILITY_COMPANY = 'COMPANY';

    protected $guarded = [];
    protected $casts = [
        'definition_json' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string { return 'code'; }
    public function owner(){ return $this->belongsTo(\App\Models\User::class,'owner_id'); }
    public function userAccess(){ return $this->hasMany(ReportUserAccess::class); }
    public function roleAccess(){ return $this->hasMany(ReportRoleAccess::class); }
    public function favorites(){ return $this->hasMany(ReportFavorite::class); }
    public function savedViews(){ return $this->hasMany(ReportSavedView::class); }
    public function executionLogs(){ return $this->hasMany(ReportExecutionLog::class); }
    public function versions(){ return $this->hasMany(ReportVersion::class); }
}
