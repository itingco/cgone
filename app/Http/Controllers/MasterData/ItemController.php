<?php
namespace App\Http\Controllers\MasterData;

use App\Models\Item;
use App\Services\MasterData\MasterWorkspaceService;

class ItemController extends AbstractMasterController
{
    protected string $modelClass = Item::class;
    protected string $menuCode = 'master.items';
    protected string $title = 'Items';
    protected array $columns = ['code'=>'Code','name'=>'Name','item_type'=>'Type','brand'=>'Brand','category'=>'Category','price_hold'=>'Price Hold','is_discontinued'=>'Discontinued'];

    protected array $fields = [
        'code'=>['label'=>'Item Code','type'=>'text'],
        'name'=>['label'=>'Item Name','type'=>'text'],
        'class_code'=>['label'=>'Class','type'=>'text','nullable'=>true],
        'item_type'=>['label'=>'Type','type'=>'select','options'=>'item_types'],
        'base_uom_id'=>['label'=>'Base / 1st Level UOM','type'=>'select','options'=>'uoms'],
        'brand_id'=>['label'=>'Brand','type'=>'select','options'=>'brands','nullable'=>true],
        'category_id'=>['label'=>'Category','type'=>'select','options'=>'categories','nullable'=>true],
        'family'=>['label'=>'Family','type'=>'text','nullable'=>true],
        'sub_family'=>['label'=>'Sub-family','type'=>'text','nullable'=>true],
        'manufacturer'=>['label'=>'Manufacturer','type'=>'text','nullable'=>true],
        'price_group'=>['label'=>'Price Group','type'=>'text','nullable'=>true],
        'incentive_schedule'=>['label'=>'Incentive Schedule','type'=>'text','nullable'=>true],
        'costing_method'=>['label'=>'Cost Method','type'=>'select','options'=>'cost_methods','nullable'=>true],
        'min_quantity'=>['label'=>'Minimum','type'=>'number','step'=>'0.0001'],
        'max_quantity'=>['label'=>'Maximum','type'=>'number','step'=>'0.0001'],
        'reorder_level'=>['label'=>'Reorder Level','type'=>'number','step'=>'0.0001'],

        'size_label'=>['label'=>'Size','type'=>'text','nullable'=>true],
        'color_label'=>['label'=>'Color','type'=>'text','nullable'=>true],
        'size_ratio'=>['label'=>'Size Ratio','type'=>'number','step'=>'0.000001','nullable'=>true],
        'length'=>['label'=>'Length (mm)','type'=>'number','step'=>'0.0001'],
        'width'=>['label'=>'Width (mm)','type'=>'number','step'=>'0.0001'],
        'thickness'=>['label'=>'Thickness (cm)','type'=>'number','step'=>'0.0001'],
        'net_weight'=>['label'=>'Net Weight (kg)','type'=>'number','step'=>'0.0001'],
        'gross_weight'=>['label'=>'Gross Weight (kg)','type'=>'number','step'=>'0.0001'],
        'volume'=>['label'=>'Volume (M3)','type'=>'number','step'=>'0.000001'],
        'require_serial_numbers'=>['label'=>'Require Serial Numbers','type'=>'checkbox'],
        'unique_serial_number'=>['label'=>'Unique Serial Number','type'=>'checkbox'],
        'auto_uom_conversion'=>['label'=>'Automatic Conversion Between UOMs','type'=>'checkbox'],
        'ignore_cost_verification'=>['label'=>'Ignore Cost of Goods Verification','type'=>'checkbox'],
        'consignment_goods'=>['label'=>'Consignment Goods','type'=>'checkbox'],
        'no_tax'=>['label'=>'No Tax','type'=>'checkbox'],
        'allow_sales_bonus'=>['label'=>'Allow Sales Bonus','type'=>'checkbox'],
        'purchase_sales_by_weight'=>['label'=>'Purchase & Sales by Weight','type'=>'checkbox'],
        'warranty_supplier_months'=>['label'=>'Warranty from Supplier (Months)','type'=>'number'],
        'warranty_customer_months'=>['label'=>'Warranty to Customer (Months)','type'=>'number'],
        'reward_points'=>['label'=>'Reward Points','type'=>'number','step'=>'0.0001'],
        'default_warehouse_id'=>['label'=>'Default Warehouse','type'=>'select','options'=>'warehouses','nullable'=>true],
        'grade'=>['label'=>'Grade','type'=>'text','nullable'=>true],
        'can_be_sold'=>['label'=>'Can Be Sold','type'=>'checkbox','default'=>true],
        'can_be_purchased'=>['label'=>'Can Be Purchased','type'=>'checkbox','default'=>true],
        'can_be_used'=>['label'=>'Can Be Used','type'=>'checkbox','default'=>true],
        'is_confidential'=>['label'=>'Confidential','type'=>'checkbox'],
        'price_hold'=>['label'=>'Price Hold','type'=>'checkbox'],
        'is_discontinued'=>['label'=>'Discontinued','type'=>'checkbox'],
        'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true],

        'procurement_method'=>['label'=>'Procurement Method','type'=>'select','options'=>'procurement_methods','nullable'=>true],
        'default_vendor_id'=>['label'=>'Default Supplier','type'=>'select','options'=>'vendors','nullable'=>true],
        'order_interval'=>['label'=>'Order Interval','type'=>'text','nullable'=>true],
        'min_order'=>['label'=>'Minimum Order','type'=>'number','step'=>'0.0001'],
        'order_multiple'=>['label'=>'Order Multiple','type'=>'number','step'=>'0.0001'],
        'lead_time_days'=>['label'=>'Lead Time (Days)','type'=>'number'],
        'sales_min_order'=>['label'=>'Sales Minimum Order','type'=>'number','step'=>'0.0001'],
        'lifetime_months'=>['label'=>'Lifetime (Months)','type'=>'number'],
        'min_markup_pct'=>['label'=>'Minimum Markup %','type'=>'number','step'=>'0.0001'],
        'max_markup_pct'=>['label'=>'Maximum Markup %','type'=>'number','step'=>'0.0001'],

        'inventory_posting_group_id'=>['label'=>'Inventory Posting Group','type'=>'select','options'=>'inventory_groups','nullable'=>true],
        'general_product_posting_group_id'=>['label'=>'General Product Posting Group','type'=>'select','options'=>'product_groups','nullable'=>true],
        'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true],
        'inventory_account_id'=>['label'=>'Inventory Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'sales_account_id'=>['label'=>'Sales Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'cogs_account_id'=>['label'=>'COGS Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'purchase_account_id'=>['label'=>'Purchase Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'adjustment_account_id'=>['label'=>'Adjustment Account','type'=>'select','options'=>'accounts','nullable'=>true],
        'specification'=>['label'=>'Specification','type'=>'textarea','nullable'=>true],
        'remarks'=>['label'=>'Remarks','type'=>'textarea','nullable'=>true],
    ];

    protected array $fieldGroups = [
        'details'=>['label'=>'Details','fields'=>['code','name','class_code','item_type','base_uom_id','brand_id','category_id','family','sub_family','manufacturer','price_group','incentive_schedule','costing_method','min_quantity','max_quantity','reorder_level']],
        'properties'=>['label'=>'Properties','fields'=>['size_label','color_label','size_ratio','length','width','thickness','net_weight','gross_weight','volume','require_serial_numbers','unique_serial_number','auto_uom_conversion','ignore_cost_verification','consignment_goods','no_tax','allow_sales_bonus','purchase_sales_by_weight','warranty_supplier_months','warranty_customer_months','reward_points','default_warehouse_id','grade','can_be_sold','can_be_purchased','can_be_used','is_confidential','price_hold','is_discontinued','is_active']],
        'planning'=>['label'=>'Planning','fields'=>['procurement_method','default_vendor_id','order_interval','min_order','order_multiple','lead_time_days','sales_min_order','lifetime_months','min_markup_pct','max_markup_pct']],
        'gl_interface'=>['label'=>'GL Interface','fields'=>['inventory_posting_group_id','general_product_posting_group_id','tax_posting_group_id','inventory_account_id','sales_account_id','cogs_account_id','purchase_account_id','adjustment_account_id']],
        'specification'=>['label'=>'Specification & Remarks','fields'=>['specification','remarks']],
    ];

    protected function rules(?int $id=null): array
    {
        $bool = ['required','boolean'];
        $num = ['required','numeric','min:0'];
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'class_code'=>['nullable','string','max:50'],
            'item_type'=>['required','in:INVENTORY,SERVICE,NON_INVENTORY'],'base_uom_id'=>['required','exists:uoms,id'],'brand_id'=>['nullable','exists:brands,id'],'category_id'=>['nullable','exists:item_categories,id'],
            'family'=>['nullable','string','max:120'],'sub_family'=>['nullable','string','max:120'],'manufacturer'=>['nullable','string','max:150'],'price_group'=>['nullable','string','max:100'],'incentive_schedule'=>['nullable','string','max:100'],
            'costing_method'=>['nullable','in:AVERAGE,FIFO,STANDARD'],'min_quantity'=>$num,'max_quantity'=>$num,'reorder_level'=>$num,
            'size_label'=>['nullable','string','max:100'],'color_label'=>['nullable','string','max:100'],'size_ratio'=>['nullable','numeric','min:0'],'length'=>$num,'width'=>$num,'thickness'=>$num,'net_weight'=>$num,'gross_weight'=>$num,'volume'=>$num,
            'require_serial_numbers'=>$bool,'unique_serial_number'=>$bool,'auto_uom_conversion'=>$bool,'ignore_cost_verification'=>$bool,'consignment_goods'=>$bool,'no_tax'=>$bool,'allow_sales_bonus'=>$bool,'purchase_sales_by_weight'=>$bool,
            'warranty_supplier_months'=>['required','integer','min:0'],'warranty_customer_months'=>['required','integer','min:0'],'reward_points'=>$num,'default_warehouse_id'=>['nullable','exists:warehouses,id'],'grade'=>['nullable','string','max:50'],
            'can_be_sold'=>$bool,'can_be_purchased'=>$bool,'can_be_used'=>$bool,'is_confidential'=>$bool,'price_hold'=>$bool,'is_discontinued'=>$bool,'is_active'=>$bool,
            'procurement_method'=>['nullable','in:BUY,MAKE'],'default_vendor_id'=>['nullable','exists:vendors,id'],'order_interval'=>['nullable','string','max:50'],'min_order'=>$num,'order_multiple'=>$num,'lead_time_days'=>['required','integer','min:0'],'sales_min_order'=>$num,'lifetime_months'=>['required','integer','min:0'],'min_markup_pct'=>$num,'max_markup_pct'=>$num,
            'inventory_posting_group_id'=>['nullable','exists:inventory_posting_groups,id'],'general_product_posting_group_id'=>['nullable','exists:general_product_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],
            'inventory_account_id'=>['nullable','exists:chart_of_accounts,id'],'sales_account_id'=>['nullable','exists:chart_of_accounts,id'],'cogs_account_id'=>['nullable','exists:chart_of_accounts,id'],'purchase_account_id'=>['nullable','exists:chart_of_accounts,id'],'adjustment_account_id'=>['nullable','exists:chart_of_accounts,id'],
            'specification'=>['nullable','string'],'remarks'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        $accounts=\App\Models\ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]);
        return [
            'item_types'=>['INVENTORY'=>'Stock / Inventory','SERVICE'=>'Service','NON_INVENTORY'=>'Non Inventory'],
            'cost_methods'=>['AVERAGE'=>'Average Cost','FIFO'=>'FIFO','STANDARD'=>'Standard Cost'],
            'procurement_methods'=>['BUY'=>'Buy','MAKE'=>'Make / Assembly'],
            'uoms'=>\App\Models\Uom::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($u)=>[$u->id=>$u->code.' - '.$u->name]),
            'categories'=>\App\Models\ItemCategory::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($x)=>[$x->id=>$x->code.' - '.$x->name]),
            'brands'=>\App\Models\Brand::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($x)=>[$x->id=>$x->code.' - '.$x->name]),
            'vendors'=>\App\Models\Vendor::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($v)=>[$v->id=>$v->code.' - '.$v->name]),
            'warehouses'=>\App\Models\Warehouse::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($w)=>[$w->id=>$w->code.' - '.$w->name]),
            'accounts'=>$accounts,
            'inventory_groups'=>\App\Models\InventoryPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'product_groups'=>\App\Models\GeneralProductPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
        ];
    }

    public function show(int $id)
    {
        $record=$this->model($id)->load(['baseUom','uoms.uom','aliases.uom','defaultVendor','defaultWarehouse','brand','category']);
        $w=app(MasterWorkspaceService::class);
        return view('master.workspace',[
            'record'=>$record,'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'menuCode'=>$this->menuCode,'workspaceType'=>'item',
            'ledgerRows'=>$w->itemLedgerHistory($id),'priceHistory'=>$w->itemPriceHistory($id),'valuationRows'=>$w->valuationHistory($record),'stockByWarehouse'=>$w->stockByWarehouse($id),'annualItemSummary'=>$w->annualItemSummary($id),'outstandingSalesOrders'=>$w->outstandingSalesOrdersByItem($id),'outstandingPurchaseOrders'=>$w->outstandingPurchaseOrdersByItem($id),'auditLogs'=>$w->auditLogs($record),
        ]);
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Item Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Item Name','type'=>'text','column'=>'name'],'item_type'=>['label'=>'Item Type','type'=>'text','column'=>'item_type'],
            'category'=>['label'=>'Category','type'=>'lookup','relation'=>'category','column'=>'name'],'brand'=>['label'=>'Brand','type'=>'lookup','relation'=>'brand','column'=>'name'],'default_vendor'=>['label'=>'Default Vendor','type'=>'lookup','relation'=>'defaultVendor','column'=>'code'],
            'costing_method'=>['label'=>'Cost Method','type'=>'text','column'=>'costing_method'],'procurement_method'=>['label'=>'Procurement','type'=>'text','column'=>'procurement_method'],'min_quantity'=>['label'=>'Min Qty','type'=>'number','column'=>'min_quantity'],'max_quantity'=>['label'=>'Max Qty','type'=>'number','column'=>'max_quantity'],'reorder_level'=>['label'=>'Reorder Level','type'=>'number','column'=>'reorder_level'],
            'price_hold'=>['label'=>'Price Hold','type'=>'boolean','column'=>'price_hold'],'is_discontinued'=>['label'=>'Discontinued','type'=>'boolean','column'=>'is_discontinued'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
