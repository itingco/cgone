<?php
$file = dirname(__DIR__).'/app/Console/Commands/SeedSampleCompany.php';
$fail=[];$pass=0;
$check=function(bool $ok,string $label)use(&$fail,&$pass){if($ok){$pass++;echo "PASS {$label}\n";}else{$fail[]=$label;echo "FAIL {$label}\n";}};
$check(is_file($file),'command file exists');
$src=is_file($file)?file_get_contents($file):'';
$needles=[
 'erp:seed-sample-company'=>'artisan signature','--database='=>'database option','--force'=>'production safety override','guardDemoDatabase'=>'non-demo database guard','DB::transaction'=>'atomic transaction',
 "DEMO-MAIN"=>'main BU',"DEMO-ONLINE"=>'second BU',"chart_of_accounts"=>'COA',"posting_setups"=>'posting setup',"DEMO-PPN11"=>'tax group',
 "DEMO-DRILL-01"=>'item 1',"DEMO-LADDER-01"=>'item 2',"DEMO-C001"=>'customer',"DEMO-V001"=>'vendor',"DEMO-PRICE-001"=>'price update',
 "transaction_templates"=>'transaction template',"sales_requests"=>'sales request',"sales_orders"=>'sales order',"shipments"=>'shipment',"sales_invoices"=>'sales invoice',
 "posted_sales_invoices"=>'posted sales invoice',"customer_ledgers"=>'customer ledger',"purchase_requests"=>'purchase request',"purchase_orders"=>'purchase order',"receipts"=>'receipt',
 "purchase_invoices"=>'purchase invoice',"posted_purchase_invoices"=>'posted purchase invoice',"vendor_ledgers"=>'vendor ledger',"gl_batches"=>'GL batch',"gl_entries"=>'GL entries',
 "goods_transfer_requests"=>'transfer request',"goods_transfers"=>'goods transfer',"adjustments"=>'inventory adjustment',"item_ledgers"=>'item ledger',
 "DEMO-E001"=>'employee manager',"DEMO-E002"=>'employee sales',"DEMO-E003"=>'employee ops',"employee_allocations"=>'employee allocation',
 "shifts"=>'shift',"shift_patterns"=>'shift pattern',"employee_shift_assignments"=>'schedule assignment',"attendance_records"=>'attendance',"attendance_corrections"=>'attendance correction',
 "leave_balances"=>'leave balance',"leave_requests"=>'leave request',"overtime_records"=>'overtime',"holidays"=>'holiday',
 "salary_components"=>'salary components',"salary_component_formulas"=>'salary formula',"employee_salary_setups"=>'employee salary setup',"employee_salary_components"=>'employee salary components',
 "one_time_payroll_inputs"=>'one-time payroll input',"payroll_rule_versions"=>'payroll rule',"tax_rule_versions"=>'tax rule',"statutory_rule_versions"=>'statutory rule',"salary_component_posting_mappings"=>'salary GL mapping',
 'PostgresDatabaseManager'=>'official PostgreSQL database manager','DatabaseRegistry'=>'runtime database registry','captureActiveIdentities'=>'capture source users and roles','syncActiveIdentities'=>'copy users and roles into target database','manager->create'=>'physical database creation','manager->initialize'=>'migration and seed initialization',
 "activity_logs"=>'audit sample',"data_views"=>'report/data-view sample',"filterColumns"=>'schema tolerant field filter',"upsertId"=>'idempotent upsert helper',
];
foreach($needles as $needle=>$label)$check(str_contains($src,$needle),$label);
$check(!preg_match('/\beval\s*\(/i',$src),'no eval');
$check(!str_contains($src,'DB::statement'),'no raw DB statement');
$check(!preg_match('/->\s*(delete|truncate)\s*\(/i',$src),'no destructive delete/truncate');
$check(!str_contains($src,'session(') && !str_contains($src,'active_business_unit'),'no global BU session context');
$check(!str_contains($src,'payroll_results') && !str_contains($src,'payroll_employees'),'does not fabricate H4 payroll result');
$check(str_contains($src,'H4 Payroll Engine belum diaktifkan'),'explicit H4 warning');
$check(!preg_match('/(?:private|protected)\s+function\s+line\s*\(/i',$src),'no collision with Artisan Command::line');
$check(str_contains($src,'private function upsertLine('),'database line helper renamed to upsertLine');
$check(substr_count($src,'$this->line(')===2,'only Artisan console line calls remain');
$check(substr_count($src,'$this->upsertLine(')>=15,'document line upserts use upsertLine helper');

if($fail){echo "\nFAILED ".count($fail)." / ".($pass+count($fail))." checks\n";exit(1);}echo "\nOK {$pass} checks\n";
