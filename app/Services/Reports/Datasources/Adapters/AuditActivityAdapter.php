<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class AuditActivityAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'AUDIT_ACTIVITY'; }
    public function name(): string { return 'Audit Activity'; }
    public function category(): string { return 'Audit'; }
    public function query(): Builder
    {
        return DB::table('activity_logs as a')
            ->leftJoin('users as u','u.id','=','a.user_id');
    }
    public function fieldMap(): array
    {
        return [
            'created_at'=>$this->field('Time','Audit','datetime','a.created_at'),
            'user_name'=>$this->field('User','Audit','string','u.name'),
            'module'=>$this->field('Module','Audit','string','a.module'),
            'action'=>$this->field('Action','Audit','string','a.action'),
            'target_type'=>$this->field('Target Type','Audit','string','a.target_type'),
            'target_id'=>$this->field('Target ID','Audit','string','a.target_id'),
            'document_number'=>$this->field('Document No','Audit','string','a.document_number'),
            'ip_address'=>$this->field('IP Address','Audit','string','a.ip_address'),
            'request_path'=>$this->field('Request Path','Audit','string','a.request_path',false,true,false,true),
        ];
    }
}
