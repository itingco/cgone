<?php

namespace App\Services\Reports\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ControlAccountResolver
{
    public function receivableAccountIds(): array
    {
        $ids=collect();

        if(Schema::hasTable('customer_posting_groups')){
            $ids=$ids->merge(DB::table('customer_posting_groups')
                ->where('is_active',true)
                ->whereNotNull('receivable_account_id')
                ->pluck('receivable_account_id'));
        }

        if(Schema::hasTable('customers') && Schema::hasColumn('customers','receivable_account_id')){
            $ids=$ids->merge(DB::table('customers')
                ->whereNotNull('receivable_account_id')
                ->pluck('receivable_account_id'));
        }

        return $this->ids($ids);
    }

    public function payableAccountIds(): array
    {
        $ids=collect();

        if(Schema::hasTable('vendor_posting_groups')){
            $ids=$ids->merge(DB::table('vendor_posting_groups')
                ->where('is_active',true)
                ->whereNotNull('payable_account_id')
                ->pluck('payable_account_id'));
        }

        if(Schema::hasTable('vendors') && Schema::hasColumn('vendors','payable_account_id')){
            $ids=$ids->merge(DB::table('vendors')
                ->whereNotNull('payable_account_id')
                ->pluck('payable_account_id'));
        }

        return $this->ids($ids);
    }

    public function inventoryAccountIds(): array
    {
        $ids=collect();

        if(Schema::hasTable('inventory_posting_groups')){
            $ids=$ids->merge(DB::table('inventory_posting_groups')
                ->where('is_active',true)
                ->whereNotNull('inventory_account_id')
                ->pluck('inventory_account_id'));
        }

        if(Schema::hasTable('items') && Schema::hasColumn('items','inventory_account_id')){
            $ids=$ids->merge(DB::table('items')
                ->whereNotNull('inventory_account_id')
                ->pluck('inventory_account_id'));
        }

        return $this->ids($ids);
    }

    private function ids(Collection $ids): array
    {
        return $ids->filter()->map(fn($id)=>(int)$id)->unique()->values()->all();
    }
}
