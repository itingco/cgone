from pathlib import Path

root = Path(__file__).resolve().parents[2]
errors = []

def text(path: str) -> str:
    p = root / path
    if not p.exists():
        errors.append(f'missing {path}')
        return ''
    return p.read_text(errors='ignore')

routes = text('routes/web.php')
for token in ["dashboard/settings", "profile/password"]:
    if token not in routes:
        errors.append(f'routes missing {token}')

layout = text('resources/views/layouts/app.blade.php')
for token in ['profile-menu', 'Department', 'Change Password', 'autoFilterCollapse']:
    if token not in layout:
        errors.append(f'layout missing {token}')

master_index = text('resources/views/master/index.blade.php')
for token in ['filterOffcanvas', 'Filter']:
    if token not in master_index:
        errors.append(f'master filter UX missing {token}')

master_show = text('resources/views/master/show.blade.php')
for token in ['masterTabs', 'Price Level', 'History']:
    if token not in master_show:
        errors.append(f'master tabs missing {token}')

controller = text('app/Http/Controllers/DashboardController.php')
if "DashboardPreference" not in controller:
    errors.append('dashboard is not preference-driven')
if "if ($preferences->isEmpty())" not in controller:
    errors.append('dashboard does not short-circuit empty layout')

for path in [
    'app/Http/Controllers/ProfileController.php',
    'app/Http/Controllers/DashboardSettingsController.php',
    'app/Models/DashboardPreference.php',
    'app/Services/Dashboard/DashboardWidgetService.php',
    'database/migrations/2026_08_28_100000_create_v4_erp_optimization.php',
    'database/seeders/DistributorTechnicalToolsCoaSeeder.php',
    'resources/views/dashboard-settings.blade.php',
    'resources/views/profile/password.blade.php',
]:
    text(path)

migration = text('database/migrations/2026_08_28_100000_create_v4_erp_optimization.php')
for token in ['user_dashboard_preferences', "string('department')", 'item_ledgers', 'customer_ledgers', 'vendor_ledgers']:
    if token not in migration:
        errors.append(f'V4 migration missing {token}')

coa = text('database/seeders/DistributorTechnicalToolsCoaSeeder.php')
for token in ['Trade Receivables', 'Merchandise Inventory', 'Trade Payables', 'Product Sales', 'Merchandise COGS', 'Corporate Income Tax']:
    if token not in coa:
        errors.append(f'COA template missing {token}')

item = text('app/Http/Controllers/MasterData/ItemController.php')
for token in ['masterTabs', 'prices', 'historyRows']:
    if token not in item:
        errors.append(f'item detail missing {token}')

customer = text('app/Http/Controllers/MasterData/CustomerController.php')
vendor = text('app/Http/Controllers/MasterData/VendorController.php')
if 'historyRows' not in customer:
    errors.append('customer history missing')
if 'historyRows' not in vendor:
    errors.append('vendor history missing')

if errors:
    print('V4 ERP OPTIMIZATION CONTRACT RED')
    print('\n'.join(errors))
    raise SystemExit(1)

print('V4 ERP optimization contract OK')
