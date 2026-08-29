<?php
namespace Database\Seeders;
use App\Models\{DocumentSequence,Location,PriceLevel,SystemSetting,Uom,Warehouse};
use Illuminate\Database\Seeder;
class MasterReferenceSeeder extends Seeder {
 public function run(): void {
  foreach([['PCS','Pieces','PCS'],['BOX','Box','BOX'],['UNIT','Unit','UNIT']] as [$code,$name,$symbol]) Uom::firstOrCreate(['code'=>$code],['name'=>$name,'symbol'=>$symbol,'is_active'=>true]);
  Warehouse::firstOrCreate(['code'=>'MAIN'],['name'=>'Main Warehouse','is_active'=>true]);
  Location::firstOrCreate(['code'=>'MAIN'],['name'=>'Main Location','bin_mandatory'=>false,'additional_discount_pct'=>0,'is_system'=>false,'is_active'=>true]);
  Location::firstOrCreate(['code'=>'IN-TRANSIT'],['name'=>'Inventory In Transit','bin_mandatory'=>false,'additional_discount_pct'=>0,'is_system'=>true,'is_active'=>true]);
  PriceLevel::firstOrCreate(['code'=>'RETAIL'],['name'=>'Retail','sort_order'=>100,'currency_code'=>'IDR','description'=>'Default retail price level','is_active'=>true]);
  $series=['ADJ'=>'ADJ/{YY}{MM}/{#####}','SALES_REQUEST'=>'SR/{YY}{MM}/{#####}','SALES_ORDER'=>'SO/{YY}{MM}/{#####}','SHIPMENT'=>'SH/{YY}{MM}/{#####}','POSTED_SHIPMENT'=>'PSH/{YY}{MM}/{#####}','SALES_INVOICE'=>'SI/{YY}{MM}/{#####}','POSTED_SALES_INVOICE'=>'PSI/{YY}{MM}/{#####}','PURCHASE_REQUEST'=>'PR/{YY}{MM}/{#####}','PURCHASE_ORDER'=>'PO/{YY}{MM}/{#####}','RECEIPT'=>'RC/{YY}{MM}/{#####}','POSTED_RECEIPT'=>'PRC/{YY}{MM}/{#####}','PURCHASE_INVOICE'=>'PI/{YY}{MM}/{#####}','POSTED_PURCHASE_INVOICE'=>'PPI/{YY}{MM}/{#####}','UNDO'=>'UNDO/{YY}{MM}/{#####}','GOODS_TRANSFER_REQUEST'=>'GTR/{YY}{MM}/{#####}','GOODS_TRANSFER'=>'GT/{YY}{MM}/{#####}','GOODS_TRANSFER_RECEIPT'=>'GRC/{YY}{MM}/{#####}','PRICE_UPDATE_BATCH'=>'PUB/{YY}{MM}/{#####}'];
  foreach($series as $code=>$pattern) DocumentSequence::firstOrCreate(['code'=>$code],['prefix'=>strtok($pattern,'/'),'date_format'=>'ym','separator'=>'/','padding'=>5,'current_number'=>0,'reset_period'=>'monthly','format_pattern'=>$pattern,'is_active'=>true]);
  SystemSetting::firstOrCreate(['key'=>'ERP.COMPANY_NAME'],['value'=>'Cipta Group Indonesia','value_type'=>'string','description'=>'Company display name','is_public'=>true]);
 }
}
