<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Item;

class ItemController extends AbstractMasterController
{
    protected string $modelClass = Item::class;
    protected string $menuCode = 'master.items';
    protected string $title = 'Items';
    protected array $columns = ['code'=>'Code','name'=>'Name','item_type'=>'Type','price_hold'=>'Price Hold','is_discontinued'=>'Discontinued'];

    protected array $fields = [
        'code'=>['label'=>'Item Code','type'=>'text','tab'=>'General'],
        'name'=>['label'=>'Item Name','type'=>'text','tab'=>'General'],
        'short_name'=>['label'=>'Short Name','type'=>'text','nullable'=>true,'tab'=>'General'],
        'item_type'=>['label'=>'Item Type','type'=>'select','options'=>'item_types','tab'=>'General'],
        'category_id'=>['label'=>'Category','type'=>'select','options'=>'categories','nullable'=>true,'tab'=>'General'],
        'brand_id'=>['label'=>'Brand','type'=>'select','options'=>'brands','nullable'=>true,'tab'=>'General'],
        'base_uom_id'=>['label'=>'Base UOM','type'=>'select','options'=>'uoms','tab'=>'General'],
        'barcode'=>['label'=>'Barcode / EAN','type'=>'text','nullable'=>true,'tab'=>'General'],
        'manufacturer_code'=>['label'=>'Manufacturer Code','type'=>'text','nullable'=>true,'tab'=>'General'],
        'model_no'=>['label'=>'Model / Type','type'=>'text','nullable'=>true,'tab'=>'General'],
        'origin_country'=>['label'=>'Country of Origin','type'=>'text','nullable'=>true,'tab'=>'General'],
        'hs_code'=>['label'=>'HS Code','type'=>'text','nullable'=>true,'tab'=>'General'],

        'default_vendor_id'=>['label'=>'Default Supplier / Vendor','type'=>'select','options'=>'vendors','nullable'=>true,'tab'=>'Inventory & Procurement'],
        'costing_method'=>['label'=>'Costing Method','type'=>'select','options'=>'costing_methods','nullable'=>true,'tab'=>'Inventory & Procurement'],
        'procurement_method'=>['label'=>'Procurement Method','type'=>'select','options'=>'procurement_methods','nullable'=>true,'tab'=>'Inventory & Procurement'],
        'min_quantity'=>['label'=>'Minimum Quantity','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'max_quantity'=>['label'=>'Maximum Quantity','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'reorder_level'=>['label'=>'Reorder Level','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'min_order'=>['label'=>'Minimum Order Qty','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'lead_time_days'=>['label'=>'Lead Time (Days)','type'=>'number','tab'=>'Inventory & Procurement'],
        'warranty_months'=>['label'=>'Warranty (Months)','type'=>'number','default'=>0,'tab'=>'Inventory & Procurement'],
        'weight'=>['label'=>'Weight','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'length'=>['label'=>'Length','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'width'=>['label'=>'Width','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],
        'height'=>['label'=>'Height','type'=>'number','step'=>'0.0001','tab'=>'Inventory & Procurement'],

        'inventory_posting_group_id'=>['label'=>'Inventory Posting Group','type'=>'select','options'=>'inventory_groups','nullable'=>true,'tab'=>'Accounting & Posting'],
        'general_product_posting_group_id'=>['label'=>'General Product Posting Group','type'=>'select','options'=>'product_groups','nullable'=>true,'tab'=>'Accounting & Posting'],
        'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true,'tab'=>'Accounting & Posting'],
        'inventory_account_id'=>['label'=>'Inventory Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Posting'],
        'sales_account_id'=>['label'=>'Sales Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Posting'],
        'cogs_account_id'=>['label'=>'COGS Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Posting'],
        'purchase_account_id'=>['label'=>'Purchase Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Posting'],
        'adjustment_account_id'=>['label'=>'Adjustment Account','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Posting'],

        'specification'=>['label'=>'Specification','type'=>'textarea','nullable'=>true,'tab'=>'Description'],
        'sales_description'=>['label'=>'Sales Description','type'=>'textarea','nullable'=>true,'tab'=>'Description'],
        'purchase_description'=>['label'=>'Purchase Description','type'=>'textarea','nullable'=>true,'tab'=>'Description'],
        'remarks'=>['label'=>'Remarks','type'=>'textarea','nullable'=>true,'tab'=>'Description'],

        'track_serial'=>['label'=>'Track Serial Number','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'track_batch'=>['label'=>'Track Batch / Lot','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'can_be_sold'=>['label'=>'Can Be Sold','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'can_be_purchased'=>['label'=>'Can Be Purchased','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'price_hold'=>['label'=>'Price Hold','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'is_discontinued'=>['label'=>'Discontinued','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true,'tab'=>'Control'],
    ];

    protected function rules(?int $id = null): array
    {
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'short_name'=>['nullable','string','max:255'],
            'item_type'=>['required','in:INVENTORY,SERVICE,NON_INVENTORY'],'base_uom_id'=>['required','exists:uoms,id'],
            'category_id'=>['nullable','exists:item_categories,id'],'brand_id'=>['nullable','exists:brands,id'],'default_vendor_id'=>['nullable','exists:vendors,id'],
            'barcode'=>['nullable','string','max:100'],'manufacturer_code'=>['nullable','string','max:100'],'model_no'=>['nullable','string','max:100'],'origin_country'=>['nullable','string','max:100'],'hs_code'=>['nullable','string','max:50'],
            'costing_method'=>['nullable','in:FIFO,AVERAGE,STANDARD,SPECIFIC'],'procurement_method'=>['nullable','in:PURCHASE,MAKE,TRANSFER'],
            'min_quantity'=>['required','numeric','min:0'],'max_quantity'=>['required','numeric','min:0'],'reorder_level'=>['required','numeric','min:0'],'min_order'=>['required','numeric','min:0'],'lead_time_days'=>['required','integer','min:0'],
            'warranty_months'=>['required','integer','min:0'],'weight'=>['required','numeric','min:0'],'length'=>['required','numeric','min:0'],'width'=>['required','numeric','min:0'],'height'=>['required','numeric','min:0'],
            'inventory_posting_group_id'=>['nullable','exists:inventory_posting_groups,id'],'general_product_posting_group_id'=>['nullable','exists:general_product_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],
            'inventory_account_id'=>['nullable','exists:chart_of_accounts,id'],'sales_account_id'=>['nullable','exists:chart_of_accounts,id'],'cogs_account_id'=>['nullable','exists:chart_of_accounts,id'],'purchase_account_id'=>['nullable','exists:chart_of_accounts,id'],'adjustment_account_id'=>['nullable','exists:chart_of_accounts,id'],
            'specification'=>['nullable','string'],'sales_description'=>['nullable','string'],'purchase_description'=>['nullable','string'],'remarks'=>['nullable','string'],
            'track_serial'=>['required','boolean'],'track_batch'=>['required','boolean'],'can_be_sold'=>['required','boolean'],'can_be_purchased'=>['required','boolean'],'price_hold'=>['required','boolean'],'is_discontinued'=>['required','boolean'],'is_active'=>['required','boolean'],
        ];
    }

    protected function options(): array
    {
        $accounts = \App\Models\ChartOfAccount::where('is_active', true)->where('allow_posting', true)->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id=>$a->code.' - '.$a->name]);
        return [
            'item_types'=>['INVENTORY'=>'Inventory','SERVICE'=>'Service','NON_INVENTORY'=>'Non Inventory'],
            'costing_methods'=>['FIFO'=>'FIFO','AVERAGE'=>'Average','STANDARD'=>'Standard','SPECIFIC'=>'Specific'],
            'procurement_methods'=>['PURCHASE'=>'Purchase','MAKE'=>'Make / Assemble','TRANSFER'=>'Transfer'],
            'uoms'=>\App\Models\Uom::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($u)=>[$u->id=>$u->code.' - '.$u->name]),
            'categories'=>\App\Models\ItemCategory::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code.' - '.$c->name]),
            'brands'=>\App\Models\Brand::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($b)=>[$b->id=>$b->code.' - '.$b->name]),
            'vendors'=>\App\Models\Vendor::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($v)=>[$v->id=>$v->code.' - '.$v->name]),
            'accounts'=>$accounts,
            'inventory_groups'=>\App\Models\InventoryPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'product_groups'=>\App\Models\GeneralProductPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
        ];
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Item Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Item Name','type'=>'text','column'=>'name'],'barcode'=>['label'=>'Barcode','type'=>'text','column'=>'barcode'],
            'item_type'=>['label'=>'Item Type','type'=>'text','column'=>'item_type'],'category'=>['label'=>'Category','type'=>'lookup','relation'=>'category','column'=>'name'],'brand'=>['label'=>'Brand','type'=>'lookup','relation'=>'brand','column'=>'name'],
            'default_vendor'=>['label'=>'Default Vendor','type'=>'lookup','relation'=>'defaultVendor','column'=>'code'],'costing_method'=>['label'=>'Costing Method','type'=>'text','column'=>'costing_method'],'procurement_method'=>['label'=>'Procurement','type'=>'text','column'=>'procurement_method'],
            'reorder_level'=>['label'=>'Reorder Level','type'=>'number','column'=>'reorder_level'],'price_hold'=>['label'=>'Price Hold','type'=>'boolean','column'=>'price_hold'],'is_discontinued'=>['label'=>'Discontinued','type'=>'boolean','column'=>'is_discontinued'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
