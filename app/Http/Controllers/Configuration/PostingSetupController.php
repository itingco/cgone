<?php
namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\{ChartOfAccount,CustomerPostingGroup,GeneralProductPostingGroup,InventoryPostingGroup,PostingSetup,TaxPostingGroup,VendorPostingGroup};
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostingSetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('menu.permission:config.posting-setup,view')->only('index');
        $this->middleware('menu.permission:config.posting-setup,edit')->except('index');
    }

    private function classes(): array
    {
        return [
            'inventory'=>InventoryPostingGroup::class,
            'product'=>GeneralProductPostingGroup::class,
            'customer'=>CustomerPostingGroup::class,
            'vendor'=>VendorPostingGroup::class,
            'tax'=>TaxPostingGroup::class,
            'default'=>PostingSetup::class,
        ];
    }

    public function index(Request $request)
    {
        $editingType=$request->string('edit_type')->toString();
        $editing=null;
        if($editingType && isset($this->classes()[$editingType]) && $editingType!=='default' && $request->filled('edit_id')) {
            $class=$this->classes()[$editingType];
            $editing=$class::findOrFail($request->integer('edit_id'));
        }

        return view('configuration.posting-setup.index',[
            'inventoryGroups'=>InventoryPostingGroup::orderBy('code')->get(),
            'productGroups'=>GeneralProductPostingGroup::orderBy('code')->get(),
            'customerGroups'=>CustomerPostingGroup::orderBy('code')->get(),
            'vendorGroups'=>VendorPostingGroup::orderBy('code')->get(),
            'taxGroups'=>TaxPostingGroup::orderBy('code')->get(),
            'defaultSetup'=>PostingSetup::where('code','DEFAULT')->first(),
            'accounts'=>ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get(),
            'editingType'=>$editingType,
            'editing'=>$editing,
        ]);
    }

    public function save(Request $request, ActivityLogService $audit)
    {
        $type=$request->validate(['type'=>'required|in:inventory,product,customer,vendor,tax,default'])['type'];
        $class=$this->classes()[$type];
        $id=$request->integer('id') ?: null;

        $rules=match($type){
            'inventory'=>['code'=>['required','string','max:50'],'name'=>'required|string|max:255','inventory_account_id'=>'required|exists:chart_of_accounts,id','cogs_account_id'=>'required|exists:chart_of_accounts,id','adjustment_account_id'=>'nullable|exists:chart_of_accounts,id'],
            'product'=>['code'=>['required','string','max:50'],'name'=>'required|string|max:255','sales_account_id'=>'required|exists:chart_of_accounts,id','purchase_account_id'=>'required|exists:chart_of_accounts,id'],
            'customer'=>['code'=>['required','string','max:50'],'name'=>'required|string|max:255','receivable_account_id'=>'required|exists:chart_of_accounts,id'],
            'vendor'=>['code'=>['required','string','max:50'],'name'=>'required|string|max:255','payable_account_id'=>'required|exists:chart_of_accounts,id'],
            'tax'=>['code'=>['required','string','max:50'],'name'=>'required|string|max:255','rate'=>'required|numeric|min:0','output_tax_account_id'=>'nullable|exists:chart_of_accounts,id','input_tax_account_id'=>'nullable|exists:chart_of_accounts,id'],
            'default'=>['grni_account_id'=>'required|exists:chart_of_accounts,id','inventory_in_transit_account_id'=>'required|exists:chart_of_accounts,id','inventory_adjustment_account_id'=>'nullable|exists:chart_of_accounts,id'],
        };

        if($type!=='default') {
            $table=(new $class)->getTable();
            $rules['code'][]=Rule::unique($table,'code')->ignore($id);
        }
        $data=$request->validate($rules);

        if($type==='default') {
            $row=$class::firstOrNew(['code'=>'DEFAULT']);
            $before=$row->exists?$row->toArray():[];
            $row->fill($data+['code'=>'DEFAULT','name'=>'Default Posting Setup','is_active'=>true])->save();
        } else {
            $row=$id?$class::findOrFail($id):new $class;
            $before=$row->exists?$row->toArray():[];
            $row->fill($data+['is_active'=>true])->save();
        }

        $audit->record('config.posting-setup',$before?'update':'create',$row,$before,$row->fresh()->toArray());
        return redirect()->route('config.posting-setup.index')->with('success','Posting setup saved.');
    }
}
