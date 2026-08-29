<?php
namespace App\Http\Controllers\MasterData;
use App\Models\PriceLevel;
class PriceLevelController extends AbstractMasterController {
 protected string $modelClass=PriceLevel::class; protected string $menuCode='master.price-levels'; protected string $title='Price Levels'; protected array $columns=['sort_order'=>'Order','code'=>'Code','name'=>'Name','currency_code'=>'Currency','description'=>'Description']; protected array $fields=['code'=>['label'=>'Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'sort_order'=>['label'=>'Order (Cheapest → Most Expensive)','type'=>'number'],'currency_code'=>['label'=>'Currency','type'=>'text'],'description'=>['label'=>'Description','type'=>'textarea'],'is_active'=>['label'=>'Active','type'=>'checkbox']];
 protected function rules(?int $id=null): array { return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'sort_order'=>['required','integer','min:1','max:9999'],'currency_code'=>['required','string','max:10'],'description'=>['nullable','string'],'is_active'=>['required','boolean']]; }
}
