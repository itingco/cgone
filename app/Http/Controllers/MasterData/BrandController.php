<?php
namespace App\Http\Controllers\MasterData;
use App\Models\Brand;
class BrandController extends AbstractMasterController
{
    protected string $modelClass=Brand::class;
    protected string $menuCode='master.brands';
    protected string $title='Brands';
    protected array $columns=['code'=>'Code','name'=>'Name'];
    protected array $fields=['code'=>['label'=>'Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'is_active'=>['label'=>'Active','type'=>'checkbox']];
    protected function rules(?int $id=null): array{return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'is_active'=>['required','boolean']];}
}
