<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Finance\AccountHierarchyService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AccountHierarchyServiceTest extends TestCase
{
    public function test_rollup_adds_each_direct_balance_to_ancestors_once(): void
    {
        $root=(object)['id'=>1,'parent_id'=>null,'code'=>'100','name'=>'Assets','balance'=>10];
        $child=(object)['id'=>2,'parent_id'=>1,'code'=>'110','name'=>'Current Assets','balance'=>20];
        $leaf=(object)['id'=>3,'parent_id'=>2,'code'=>'111','name'=>'Cash','balance'=>30];

        $accounts=new Collection([$root,$child,$leaf]);

        (new AccountHierarchyService)->rollup($accounts,['balance']);

        $this->assertSame(60.0,$root->balance);
        $this->assertSame(50.0,$child->balance);
        $this->assertSame(30.0,$leaf->balance);
    }
}
