<?php

namespace Database\Seeders;

use App\Models\{Menu, Permission, Role, RoleMenuPermission};
use App\Models\HumanCapital\Payroll\{PayrollRuleVersion, StatutoryRuleVersion, TaxRuleVersion};
use Illuminate\Database\Seeder;

final class HumanCapitalSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['view','create','edit','delete','approve','export','print'])
            ->mapWithKeys(fn ($code) => [$code => Permission::firstOrCreate(['code' => $code], ['name' => ucfirst($code)])]);
        $payrollPermissions = collect([
            'payroll.setup.view' => 'Payroll Setup View',
            'payroll.setup.edit' => 'Payroll Setup Edit',
            'payroll.salary.view' => 'Payroll Salary View',
            'payroll.calculate' => 'Payroll Calculate',
            'payroll.review' => 'Payroll Review',
        ])->mapWithKeys(fn ($name,$code) => [$code => Permission::firstOrCreate(['code'=>$code],['name'=>$name])]);

        $admin = Role::firstOrCreate(['code' => 'ADMINISTRATOR'], ['name' => 'Administrator', 'description' => 'Full ERP access', 'is_active' => true]);

        $hr = Menu::updateOrCreate(['code' => 'section.human-capital'], [
            'parent_id' => null,
            'label' => 'Human Capital',
            'route_name' => null,
            'sort_order' => 650,
            'is_active' => true,
        ]);

        $config = Menu::firstOrCreate(['code' => 'section.config'], [
            'parent_id' => null,
            'label' => 'Configuration',
            'sort_order' => 700,
            'is_active' => true,
        ]);

        $menus = [
            [$hr->id, 'hr.employees', 'Employee Master', 'hr.employees.index', 1],
            [$hr->id, 'hr.allocations', 'Employee Allocation History', 'hr.allocations.index', 2],
            [$hr->id, 'hr.departments', 'Departments', 'hr.organization.index', 10],
            [$hr->id, 'hr.sub-departments', 'Sub Departments', 'hr.organization.index', 11],
            [$hr->id, 'hr.positions', 'Positions / Job Titles', 'hr.organization.index', 12],
            [$hr->id, 'hr.levels', 'Employee Levels', 'hr.organization.index', 13],
            [$hr->id, 'hr.groups', 'Employee Groups', 'hr.organization.index', 14],
            [$hr->id, 'hr.workgroups', 'Workgroups', 'hr.organization.index', 15],
            [$hr->id, 'hr.teams', 'Teams', 'hr.organization.index', 16],
            [$hr->id, 'hr.office-locations', 'Office Locations', 'hr.organization.index', 17],
            [$hr->id, 'hr.payroll-groups', 'Payroll Groups', 'hr.organization.index', 18],
            [$hr->id, 'hr.shifts', 'Shifts', 'hr.shifts.index', 30],
            [$hr->id, 'hr.schedules', 'Work Schedules', 'hr.schedules.index', 31],
            [$hr->id, 'hr.attendance', 'Attendance', 'hr.attendance.index', 32],
            [$hr->id, 'hr.attendance-corrections', 'Attendance Corrections', 'hr.attendance-corrections.index', 33],
            [$hr->id, 'hr.leave', 'Leave / Permission', 'hr.leave.index', 34],
            [$hr->id, 'hr.overtime', 'Overtime', 'hr.overtime.index', 35],
            [$hr->id, 'hr.holidays', 'Holiday Calendar', 'hr.holidays.index', 36],
            [$hr->id, 'payroll.salary-components', 'Salary Components', 'payroll.salary-components.index', 50],
            [$hr->id, 'payroll.salary-setups', 'Employee Salary Setup', 'payroll.salary-setups.index', 51],
            [$hr->id, 'payroll.one-time-inputs', 'One-time Income / Deduction', 'payroll.one-time-inputs.index', 52],
            [$hr->id, 'payroll.rules', 'Payroll / Tax / Statutory Rules', 'payroll.rules.index', 53],
            [$hr->id, 'payroll.runs', 'Payroll Calculation', 'payroll.runs.index', 54],
            [$config->id, 'config.transaction-templates', 'Transaction Templates', 'transaction-templates.index', 50],
        ];

        foreach ($menus as [$parentId, $code, $label, $route, $sort]) {
            $menu = Menu::updateOrCreate(['code' => $code], [
                'parent_id' => $parentId,
                'label' => $label,
                'route_name' => $route,
                'sort_order' => $sort,
                'is_active' => true,
            ]);
            foreach ($permissions as $permission) {
                RoleMenuPermission::firstOrCreate([
                    'role_id' => $admin->id,
                    'menu_id' => $menu->id,
                    'permission_id' => $permission->id,
                ]);
            }
            if (str_starts_with($code, 'payroll.')) {
                foreach ($payrollPermissions as $permission) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $admin->id,
                        'menu_id' => $menu->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        PayrollRuleVersion::updateOrCreate(['code'=>'PAYROLL-LEGACY-V1'],[
            'name'=>'Legacy Payroll Rule V1',
            'effective_from'=>'2000-01-01',
            'effective_to'=>null,
            'engine_key'=>'legacy_payroll_v1',
            'is_executable'=>true,
            'is_active'=>true,
            'configuration'=>['source'=>'legacy_sql_server','status'=>'H4_TRANSLATED','prorate_divisor'=>25],
            'notes'=>'H4 executable legacy payroll rule baseline. Validate against imported legacy parity fixtures before production cutover.',
        ]);
        TaxRuleVersion::updateOrCreate(['code'=>'TAX-LEGACY-V1'],[
            'name'=>'Legacy PPh21 / TER Rule Reference',
            'effective_from'=>'2000-01-01',
            'effective_to'=>null,
            'engine_key'=>'legacy_ter_v1',
            'is_executable'=>true,
            'is_active'=>true,
            'configuration'=>['status'=>'H4_TRANSLATED','method'=>'TER'],
            'notes'=>'H4 executable TER table derived from approved legacy payroll script. Validate with parity fixtures before production cutover.',
        ]);
        StatutoryRuleVersion::updateOrCreate(['code'=>'BPJS-LEGACY-V1'],[
            'name'=>'Legacy BPJS Rule Reference',
            'rule_type'=>'BPJS',
            'effective_from'=>'2000-01-01',
            'effective_to'=>null,
            'engine_key'=>'legacy_bpjs_v1',
            'is_executable'=>true,
            'is_active'=>true,
            'configuration'=>['status'=>'H4_TRANSLATED','employee_rate'=>0.02,'base_component_codes'=>['GAPOK','DEMO-BASIC']],
            'notes'=>'H4 configurable employee statutory deduction baseline. Review statutory configuration before production cutover.',
        ]);

        foreach ([$hr, $config] as $section) {
            foreach ($permissions as $permission) {
                RoleMenuPermission::firstOrCreate([
                    'role_id' => $admin->id,
                    'menu_id' => $section->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
