<?php

namespace App\Services\Reports\Drilldown;

use App\Models\Reports\ReportDefinition;
use Illuminate\Support\Facades\URL;

final class DrilldownRegistry
{
    /** @var array<string,DrilldownDefinition> */
    private array $definitions=[];

    public function __construct()
    {
        foreach([
            new DrilldownDefinition('SALES_SUMMARY','document_date','report','SALES_DETAIL',[
                'date_from'=>'document_date','date_to'=>'document_date','business_unit_id'=>'business_unit_id',
            ],['customer_id','item_id','category_id','brand_id','location_id','price_level_id']),
            new DrilldownDefinition('SALES_BY_CUSTOMER','customer_code','report','SALES_DETAIL',[
                'customer_id'=>'customer_id','business_unit_id'=>'business_unit_id',
            ],['date_from','date_to','item_id','category_id','brand_id','location_id','price_level_id']),
            new DrilldownDefinition('SALES_DETAIL','document_no','posted_document','sales-invoice',[
                'document_no'=>'document_no','document_type'=>'document_type',
            ]),
            new DrilldownDefinition('SALES_DETAIL','item_code','route','master.items.show',['id'=>'item_id']),
            new DrilldownDefinition('TRIAL_BALANCE','code','report','GENERAL_LEDGER_DETAIL',['account_id'=>'id'],[
                'date_from','date_to','business_unit_id',
            ]),
            new DrilldownDefinition('GENERAL_LEDGER_DETAIL','document_number','source_document','source_document',[
                'document_number'=>'document_number','document_type'=>'document_type',
            ]),
        ] as $definition){
            $this->definitions[$this->key($definition->sourceReport,$definition->column)]=$definition;
        }
    }

    public function resolve(string $reportCode,string $column): ?DrilldownDefinition
    {
        return $this->definitions[$this->key($reportCode,$column)]??null;
    }

    public function signedUrl(
        ReportDefinition $definition,
        string $column,
        object|array $row,
        array $parameters,
    ): ?string {
        if($definition->report_type!==ReportDefinition::TYPE_STANDARD) return null;
        $code=(string)data_get($definition->definition_json,'standard_code',$definition->code);
        $drill=$this->resolve($code,$column);
        if(!$drill) return null;
        if(in_array($code,['SALES_SUMMARY','SALES_BY_CUSTOMER'],true)
            && array_key_exists('business_unit_id',$drill->rowMap)
            && (data_get($row,'business_unit_id')===null || data_get($row,'business_unit_id')==='')) return null;
        if($drill->targetType==='source_document'
            && !in_array(strtoupper((string)data_get($row,'document_type')),['POSTED_SALES_INVOICE','POSTED_PURCHASE_INVOICE','POSTED_SHIPMENT','POSTED_RECEIPT'],true)) return null;

        $payload=base64_encode(json_encode($drill->payload($row,$parameters),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        return URL::temporarySignedRoute('reports.drilldown',now()->addMinutes(20),[
            'report'=>$definition->code,
            'column'=>$column,
            'payload'=>$payload,
        ]);
    }

    private function key(string $report,string $column): string
    {
        return strtoupper($report).'|'.$column;
    }
}
