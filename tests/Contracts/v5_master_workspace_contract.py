from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

def read(path):
    p = ROOT / path
    assert p.exists(), f"missing {path}"
    return p.read_text(encoding='utf-8')


def test_required_workspace_files_exist():
    required = [
        'app/Models/BusinessPartnerAddress.php',
        'app/Services/MasterData/MasterWorkspaceService.php',
        'app/Http/Controllers/MasterData/BusinessPartnerAddressController.php',
        'routes/master_workspace.php',
        'resources/views/master/workspace.blade.php',
        'database/migrations/2026_08_30_000100_create_business_partner_addresses.php',
        'APPLY_UPDATE.bat',
    ]
    for path in required:
        assert (ROOT / path).exists(), f"missing {path}"


def test_grouped_master_forms_and_workspace_tabs():
    form = read('resources/views/master/form.blade.php')
    assert 'fieldGroups' in form and 'master-tab-scroll' in form
    workspace = read('resources/views/master/workspace.blade.php')
    for label in ['Ledger History', 'Audit Log', 'Pending Invoices', 'Payment History', 'Valuation & COGS', 'Price Level History']:
        assert label in workspace


def test_address_persistence_is_polymorphic():
    model = read('app/Models/BusinessPartnerAddress.php')
    assert 'morphTo' in model and "addressable" in model
    migration = read('database/migrations/2026_08_30_000100_create_business_partner_addresses.php')
    assert 'morphs' in migration and 'business_partner_addresses' in migration


def test_history_service_uses_existing_ledgers_and_audit():
    service = read('app/Services/MasterData/MasterWorkspaceService.php')
    for token in ['CustomerLedger', 'VendorLedger', 'ItemLedger', 'ActivityLog', 'ItemPrice']:
        assert token in service
    assert 'fifoOutstandingInvoices' in service
    assert 'valuationHistory' in service


def test_routes_include_address_actions():
    routes = read('routes/master_workspace.php')
    assert 'BusinessPartnerAddressController' in routes
    assert "master.partner-addresses.store" in routes
    assert "master.partner-addresses.destroy" in routes
    provider = read('app/Providers/RouteServiceProvider.php')
    assert 'master_workspace.php' in provider
