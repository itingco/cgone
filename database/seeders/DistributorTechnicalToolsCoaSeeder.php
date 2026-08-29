<?php
namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class DistributorTechnicalToolsCoaSeeder extends Seeder
{
    public function run(): void
    {
        // Template is only applied to a new/empty chart so existing company COA is never overwritten.
        if (ChartOfAccount::query()->exists()) return;

        $accounts = [
            // code, name, type, normal, allow_posting, parent_code
            ['1000','ASSETS','ASSET','DEBIT',false,null],
            ['1100','Cash & Bank','ASSET','DEBIT',false,'1000'],
            ['1101','Cash on Hand','ASSET','DEBIT',true,'1100'],
            ['1111','Bank - IDR','ASSET','DEBIT',true,'1100'],
            ['1200','Receivables','ASSET','DEBIT',false,'1000'],
            ['1201','Trade Receivables','ASSET','DEBIT',true,'1200'],
            ['1300','Inventory','ASSET','DEBIT',false,'1000'],
            ['1301','Merchandise Inventory','ASSET','DEBIT',true,'1300'],
            ['1302','Goods in Transit','ASSET','DEBIT',true,'1300'],
            ['1400','Tax & Prepaid Assets','ASSET','DEBIT',false,'1000'],
            ['1401','VAT Input','ASSET','DEBIT',true,'1400'],
            ['1402','Prepaid / Withholding Tax','ASSET','DEBIT',true,'1400'],
            ['1501','Prepaid Expenses','ASSET','DEBIT',true,'1000'],
            ['1600','Fixed Assets','ASSET','DEBIT',false,'1000'],
            ['1601','Office & Warehouse Equipment','ASSET','DEBIT',true,'1600'],
            ['1602','Vehicles','ASSET','DEBIT',true,'1600'],
            ['1691','Accumulated Depreciation','ASSET','CREDIT',true,'1600'],

            ['2000','LIABILITIES','LIABILITY','CREDIT',false,null],
            ['2101','Trade Payables','LIABILITY','CREDIT',true,'2000'],
            ['2201','Accrued Expenses','LIABILITY','CREDIT',true,'2000'],
            ['2202','Customer Deposits / Advances','LIABILITY','CREDIT',true,'2000'],
            ['2300','Tax Payables','LIABILITY','CREDIT',false,'2000'],
            ['2301','VAT Output','LIABILITY','CREDIT',true,'2300'],
            ['2302','Withholding Tax Payable','LIABILITY','CREDIT',true,'2300'],
            ['2401','Short-term Bank Loan','LIABILITY','CREDIT',true,'2000'],

            ['3000','EQUITY','EQUITY','CREDIT',false,null],
            ['3101','Paid-in Capital','EQUITY','CREDIT',true,'3000'],
            ['3201','Retained Earnings','EQUITY','CREDIT',true,'3000'],
            ['3301','Current Year Earnings','EQUITY','CREDIT',true,'3000'],

            ['4000','REVENUE','REVENUE','CREDIT',false,null],
            ['4101','Product Sales','REVENUE','CREDIT',true,'4000'],
            ['4102','Service Income','REVENUE','CREDIT',true,'4000'],
            ['4191','Sales Returns','REVENUE','DEBIT',true,'4000'],
            ['4192','Sales Discounts','REVENUE','DEBIT',true,'4000'],

            ['5000','COST OF GOODS SOLD','EXPENSE','DEBIT',false,null],
            ['5101','Merchandise COGS','EXPENSE','DEBIT',true,'5000'],
            ['5102','Inbound Freight / Landed Cost','EXPENSE','DEBIT',true,'5000'],
            ['5103','Inventory Variance','EXPENSE','DEBIT',true,'5000'],

            ['6000','SELLING EXPENSES','EXPENSE','DEBIT',false,null],
            ['6101','Sales Salaries','EXPENSE','DEBIT',true,'6000'],
            ['6102','Sales Commission & Incentive','EXPENSE','DEBIT',true,'6000'],
            ['6103','Promotion & Advertising','EXPENSE','DEBIT',true,'6000'],
            ['6104','Freight Out / Delivery','EXPENSE','DEBIT',true,'6000'],

            ['7000','GENERAL & ADMIN EXPENSES','EXPENSE','DEBIT',false,null],
            ['7101','Administrative Salaries','EXPENSE','DEBIT',true,'7000'],
            ['7102','Rent','EXPENSE','DEBIT',true,'7000'],
            ['7103','Electricity & Water','EXPENSE','DEBIT',true,'7000'],
            ['7104','Internet & Telephone','EXPENSE','DEBIT',true,'7000'],
            ['7105','Office Supplies','EXPENSE','DEBIT',true,'7000'],
            ['7106','Repairs & Maintenance','EXPENSE','DEBIT',true,'7000'],
            ['7107','Software & IT','EXPENSE','DEBIT',true,'7000'],
            ['7110','Bank Charges','EXPENSE','DEBIT',true,'7000'],
            ['7111','Depreciation Expense','EXPENSE','DEBIT',true,'7000'],

            ['8000','OTHER INCOME / EXPENSE','EXPENSE','DEBIT',false,null],
            ['8101','Interest Income','REVENUE','CREDIT',true,'8000'],
            ['8201','Interest Expense','EXPENSE','DEBIT',true,'8000'],
            ['8301','Foreign Exchange Gain','REVENUE','CREDIT',true,'8000'],
            ['8302','Foreign Exchange Loss','EXPENSE','DEBIT',true,'8000'],

            ['9000','INCOME TAX','EXPENSE','DEBIT',false,null],
            ['9101','Corporate Income Tax','EXPENSE','DEBIT',true,'9000'],
        ];

        $ids = [];
        foreach ($accounts as [$code,$name,$type,$normal,$allow,$parentCode]) {
            $parentId = $parentCode ? ($ids[$parentCode] ?? ChartOfAccount::where('code',$parentCode)->value('id')) : null;
            $row = ChartOfAccount::updateOrCreate(['code'=>$code],[
                'parent_id'=>$parentId,'name'=>$name,'account_type'=>$type,'normal_balance'=>$normal,
                'allow_posting'=>$allow,'is_active'=>true,'currency_code'=>'IDR',
            ]);
            $ids[$code] = $row->id;
        }
    }
}
