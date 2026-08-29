<?php
namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\{Item,PriceLevel};
use App\Models\Pricing\ItemPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemPriceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(app(\App\Services\Security\MenuAuthorizationService::class)->allows($request->user(),'pricing.price-levels','view'),403);

        $asOf = $request->filled('as_of') ? Carbon::parse($request->input('as_of'))->startOfDay() : today();
        $levels = PriceLevel::where('is_active',true)->orderBy('sort_order')->orderBy('code')->get();
        $items = Item::where('is_active',true)
            ->when($request->filled('q'),fn($query)=>$query->where(fn($x)=>$x->where('code','like','%'.$request->input('q').'%')->orWhere('name','like','%'.$request->input('q').'%')))
            ->orderBy('code')->paginate(50)->withQueryString();

        $itemIds = $items->getCollection()->pluck('id');
        $prices = ItemPrice::whereIn('item_id',$itemIds)
            ->where('is_active',true)
            ->orderBy('effective_from')
            ->get()
            ->groupBy('item_id');

        return view('pricing.item-prices.index',compact('levels','items','prices','asOf'));
    }
}
