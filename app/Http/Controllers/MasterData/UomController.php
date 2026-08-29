<?php
namespace App\Http\Controllers\MasterData;
use App\Models\Uom;
class UomController extends AbstractMasterController {
 protected string $modelClass=Uom::class; protected string $menuCode='master.uoms'; protected string $title='Units of Measure'; protected array $columns=['code'=>'Code','name'=>'Name','symbol'=>'Symbol']; protected array $fields=['code'=>['label'=>'Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'symbol'=>['label'=>'Symbol','type'=>'text'],'is_active'=>['label'=>'Active','type'=>'checkbox']];
 protected function rules(?int $id=null): array { return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'symbol'=>['nullable','string','max:20'],'is_active'=>['required','boolean']]; }
 protected function options(): array { $id=request()->route('id'); return []; }
}
