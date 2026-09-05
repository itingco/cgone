<?php

namespace App\Services\Reports\Datasources;

use Illuminate\Database\Query\Builder;

interface ReportDatasourceAdapter
{
    public function code(): string;
    public function name(): string;
    public function category(): string;
    public function query(): Builder;

    /**
     * @return array<string,array{
     *   label:string,group_label:string,data_type:string,expression:string,
     *   aggregate_allowed:bool,filter_allowed:bool,group_allowed:bool,sort_allowed:bool,
     *   format:string,numeric:bool
     * }>
     */
    public function fieldMap(): array;
}
