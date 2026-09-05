<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\ReportDefinition;
use App\Services\Reports\{ReportAccessService};
use App\Services\Reports\Drilldown\DrilldownRegistry;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportDrilldownController extends Controller
{
    public function __construct(
        private readonly DrilldownRegistry $registry,
        private readonly ReportAccessService $access,
        private readonly MenuAuthorizationService $menuAuth,
    ) {}

    public function __invoke(Request $request,ReportDefinition $report,string $column)
    {
        abort_unless($request->hasValidSignature(),403,'Drill-down link has expired or is invalid.');
        abort_unless($this->menuAuth->allows($request->user(),'reports.center','view'),403);
        abort_unless($this->access->allows($request->user(),$report,'view'),403);
        abort_unless($report->report_type===ReportDefinition::TYPE_STANDARD,404);

        $sourceCode=(string)data_get($report->definition_json,'standard_code',$report->code);
        $definition=$this->registry->resolve($sourceCode,$column);
        abort_unless($definition,404);

        $payload=json_decode(base64_decode((string)$request->query('payload',''),true)?:'',true);
        abort_unless(is_array($payload),422,'Invalid drill-down payload.');

        return match($definition->targetType){
            'report'=>$this->toReport($request,$definition->target,$payload),
            'route'=>$this->toRoute($request,$definition->target,$payload),
            'posted_document'=>$this->toPostedDocument($request,$payload,$definition->target),
            'source_document'=>$this->toPostedDocument($request,$payload,null),
            default=>abort(404),
        };
    }

    private function toReport(Request $request,string $target,array $payload)
    {
        $report=ReportDefinition::query()->where('code',$target)->where('is_active',true)->firstOrFail();
        abort_unless($this->access->allows($request->user(),$report,'view'),403);
        return redirect()->route('reports.run',array_merge(['report'=>$report],$payload));
    }

    private function toRoute(Request $request,string $route,array $payload)
    {
        $menu=match($route){
            'master.items.show'=>'master.items',
            default=>null,
        };
        abort_unless($menu && $this->menuAuth->allows($request->user(),$menu,'view'),403);
        return redirect()->route($route,$payload);
    }

    private function toPostedDocument(Request $request,array $payload,?string $forcedType)
    {
        $documentNo=(string)($payload['document_no']??'');
        $documentType=strtoupper((string)($payload['document_type']??''));
        $type=$forcedType ?: match($documentType){
            'POSTED_SALES_INVOICE'=>'sales-invoice',
            'POSTED_PURCHASE_INVOICE'=>'purchase-invoice',
            'POSTED_SHIPMENT'=>'shipment',
            'POSTED_RECEIPT'=>'receipt',
            default=>null,
        };
        abort_unless($type && $documentNo!=='',404);

        [$table,$menu]=match($type){
            'sales-invoice'=>['posted_sales_invoices','sales.posted-invoice'],
            'purchase-invoice'=>['posted_purchase_invoices','purchase.posted-invoice'],
            'shipment'=>['posted_shipments','sales.posted-shipment'],
            'receipt'=>['posted_receipts','purchase.posted-receipt'],
            default=>[null,null],
        };
        abort_unless($table && $menu && $this->menuAuth->allows($request->user(),$menu,'view'),403);
        $id=DB::table($table)->where('document_no',$documentNo)->value('id');
        abort_unless($id,404);
        return redirect()->route('posted.show',['type'=>$type,'id'=>$id]);
    }
}
