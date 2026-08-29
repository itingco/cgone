<?php
namespace App\Http\Controllers\MasterData;
use App\Models\ItemCategory;
class ItemCategoryController extends AbstractMasterController
{
    protected string $modelClass=ItemCategory::class;
    protected string $menuCode='master.item-categories';
    protected string $title='Item Categories';
    protected array $columns=['code'=>'Code','name'=>'Name'];
    protected array $fields=['code'=>['label'=>'Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'is_active'=>['label'=>'Active','type'=>'checkbox']];
    protected function rules(?int $id=null): array{return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'is_active'=>['required','boolean']];}
}
