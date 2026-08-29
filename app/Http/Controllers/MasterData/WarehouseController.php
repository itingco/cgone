<?php
namespace App\Http\Controllers\MasterData;
use App\Models\Warehouse;
class WarehouseController extends AbstractMasterController {
 protected string $modelClass=Warehouse::class; protected string $menuCode='master.warehouses'; protected string $title='Warehouses'; protected array $columns=['code'=>'Code','name'=>'Name','address'=>'Address']; protected array $fields=['code'=>['label'=>'Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'address'=>['label'=>'Address','type'=>'textarea'],'is_active'=>['label'=>'Active','type'=>'checkbox']];
 protected function rules(?int $id=null): array { return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'address'=>['nullable','string'],'is_active'=>['required','boolean']]; }
 protected function options(): array { $id=request()->route('id'); return []; }
}
