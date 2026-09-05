$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$logFile = Join-Path (Get-Location) "cgone_replace_erp_master_migration_$stamp.txt"

function Pause-End {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "SCRIPT SELESAI / BERHENTI" -ForegroundColor Yellow
    Write-Host "Log:" -ForegroundColor Yellow
    Write-Host $logFile -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Tekan ENTER untuk menutup"
}

try {
    Start-Transcript -Path $logFile -Force | Out-Null

    if (-not (Test-Path ".\artisan")) {
        throw "Jalankan dari root project CGOne."
    }

    $target = ".\database\migrations\2026_08_30_000200_expand_erp_master_fields.php"
    $ref    = ".\database\migrations\2026_08_30_000200_expand_reference_master_fields.php"

    if (-not (Test-Path $target)) {
        throw "Target migration tidak ditemukan: $target"
    }

    if (-not (Test-Path $ref)) {
        throw "Reference migration tidak ditemukan: $ref"
    }

    Write-Host "CGOne - Replace ERP Master Expansion Migration" -ForegroundColor Cyan
    Write-Host ""

    $backupDir = ".\migration_backup_final_$stamp"
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Copy-Item $target (Join-Path $backupDir (Split-Path $target -Leaf)) -Force
    Copy-Item $ref (Join-Path $backupDir (Split-Path $ref -Leaf)) -Force

    Write-Host "[OK] Backup: $backupDir" -ForegroundColor Green

    $php = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addItems();
        $this->addCustomers();
        $this->addVendors();
        $this->addAccounts();
    }

    private function addItems(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        $missing = $this->missingColumns('items', [
            'short_name',
            'barcode',
            'manufacturer_code',
            'model_no',
            'origin_country',
            'hs_code',
            'warranty_months',
            'weight',
            'height',
            'track_serial',
            'track_batch',
            'purchase_description',
            'sales_description',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('items', function (Blueprint $t) use ($missing) {
            if (in_array('short_name', $missing, true)) {
                $t->string('short_name')->nullable();
            }
            if (in_array('barcode', $missing, true)) {
                $t->string('barcode', 100)->nullable();
            }
            if (in_array('manufacturer_code', $missing, true)) {
                $t->string('manufacturer_code', 100)->nullable();
            }
            if (in_array('model_no', $missing, true)) {
                $t->string('model_no', 100)->nullable();
            }
            if (in_array('origin_country', $missing, true)) {
                $t->string('origin_country', 100)->nullable();
            }
            if (in_array('hs_code', $missing, true)) {
                $t->string('hs_code', 50)->nullable();
            }
            if (in_array('warranty_months', $missing, true)) {
                $t->unsignedInteger('warranty_months')->default(0);
            }
            if (in_array('weight', $missing, true)) {
                $t->decimal('weight', 19, 4)->default(0);
            }
            if (in_array('height', $missing, true)) {
                $t->decimal('height', 19, 4)->default(0);
            }
            if (in_array('track_serial', $missing, true)) {
                $t->boolean('track_serial')->default(false);
            }
            if (in_array('track_batch', $missing, true)) {
                $t->boolean('track_batch')->default(false);
            }
            if (in_array('purchase_description', $missing, true)) {
                $t->text('purchase_description')->nullable();
            }
            if (in_array('sales_description', $missing, true)) {
                $t->text('sales_description')->nullable();
            }
        });
    }

    private function addCustomers(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $missing = $this->missingColumns('customers', [
            'contact_person',
            'mobile',
            'salesperson_code',
            'tax_name',
            'is_pkp',
            'credit_hold',
            'notes',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('customers', function (Blueprint $t) use ($missing) {
            if (in_array('contact_person', $missing, true)) {
                $t->string('contact_person')->nullable();
            }
            if (in_array('mobile', $missing, true)) {
                $t->string('mobile', 50)->nullable();
            }
            if (in_array('salesperson_code', $missing, true)) {
                $t->string('salesperson_code', 100)->nullable();
            }
            if (in_array('tax_name', $missing, true)) {
                $t->string('tax_name')->nullable();
            }
            if (in_array('is_pkp', $missing, true)) {
                $t->boolean('is_pkp')->default(false);
            }
            if (in_array('credit_hold', $missing, true)) {
                $t->boolean('credit_hold')->default(false);
            }
            if (in_array('notes', $missing, true)) {
                $t->text('notes')->nullable();
            }
        });
    }

    private function addVendors(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        $missing = $this->missingColumns('vendors', [
            'contact_person',
            'mobile',
            'postal_code',
            'lead_time_days',
            'min_order_value',
            'tax_name',
            'is_pkp',
            'purchase_hold',
            'notes',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('vendors', function (Blueprint $t) use ($missing) {
            if (in_array('contact_person', $missing, true)) {
                $t->string('contact_person')->nullable();
            }
            if (in_array('mobile', $missing, true)) {
                $t->string('mobile', 50)->nullable();
            }
            if (in_array('postal_code', $missing, true)) {
                $t->string('postal_code', 30)->nullable();
            }
            if (in_array('lead_time_days', $missing, true)) {
                $t->unsignedInteger('lead_time_days')->default(0);
            }
            if (in_array('min_order_value', $missing, true)) {
                $t->decimal('min_order_value', 19, 4)->default(0);
            }
            if (in_array('tax_name', $missing, true)) {
                $t->string('tax_name')->nullable();
            }
            if (in_array('is_pkp', $missing, true)) {
                $t->boolean('is_pkp')->default(false);
            }
            if (in_array('purchase_hold', $missing, true)) {
                $t->boolean('purchase_hold')->default(false);
            }
            if (in_array('notes', $missing, true)) {
                $t->text('notes')->nullable();
            }
        });
    }

    private function addAccounts(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $missing = $this->missingColumns('chart_of_accounts', [
            'account_subcategory',
            'report_group',
            'cash_flow_category',
            'external_code',
            'is_control_account',
            'reconciliation_required',
            'notes',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $t) use ($missing) {
            if (in_array('account_subcategory', $missing, true)) {
                $t->string('account_subcategory', 100)->nullable();
            }
            if (in_array('report_group', $missing, true)) {
                $t->string('report_group', 100)->nullable();
            }
            if (in_array('cash_flow_category', $missing, true)) {
                $t->string('cash_flow_category', 100)->nullable();
            }
            if (in_array('external_code', $missing, true)) {
                $t->string('external_code', 100)->nullable();
            }
            if (in_array('is_control_account', $missing, true)) {
                $t->boolean('is_control_account')->default(false);
            }
            if (in_array('reconciliation_required', $missing, true)) {
                $t->boolean('reconciliation_required')->default(false);
            }
            if (in_array('notes', $missing, true)) {
                $t->text('notes')->nullable();
            }
        });
    }

    private function missingColumns(string $table, array $columns): array
    {
        $existing = array_map(
            static fn ($column) => strtolower((string) $column),
            Schema::getColumnListing($table)
        );

        return array_values(array_filter(
            $columns,
            static fn ($column) => ! in_array(strtolower($column), $existing, true)
        ));
    }

    public function down(): void
    {
        $this->dropColumns('items', [
            'short_name',
            'barcode',
            'manufacturer_code',
            'model_no',
            'origin_country',
            'hs_code',
            'warranty_months',
            'weight',
            'height',
            'track_serial',
            'track_batch',
            'purchase_description',
            'sales_description',
        ]);

        $this->dropColumns('customers', [
            'contact_person',
            'mobile',
            'salesperson_code',
            'tax_name',
            'is_pkp',
            'credit_hold',
            'notes',
        ]);

        $this->dropColumns('vendors', [
            'contact_person',
            'mobile',
            'postal_code',
            'lead_time_days',
            'min_order_value',
            'tax_name',
            'is_pkp',
            'purchase_hold',
            'notes',
        ]);

        $this->dropColumns('chart_of_accounts', [
            'account_subcategory',
            'report_group',
            'cash_flow_category',
            'external_code',
            'is_control_account',
            'reconciliation_required',
            'notes',
        ]);
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_map(
            static fn ($column) => strtolower((string) $column),
            Schema::getColumnListing($table)
        );

        $toDrop = array_values(array_filter(
            $columns,
            static fn ($column) => in_array(strtolower($column), $existing, true)
        ));

        if ($toDrop !== []) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($toDrop));
        }
    }
};
'@

    $utf8 = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText((Resolve-Path $target), $php, $utf8)

    Write-Host "[OK] Migration utama sudah ditimpa dengan versi final." -ForegroundColor Green
    Write-Host ""

    Write-Host ">>> PHP lint"
    php -l $target
    if ($LASTEXITCODE -ne 0) {
        throw "PHP lint gagal."
    }

    Write-Host ""
    Write-Host ">>> Clear cache"
    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) {
        throw "optimize:clear gagal."
    }

    Write-Host ""
    Write-Host ">>> Audit duplicate migration"

    $audit = ".\cgone_audit_duplicate_migrations.php"

    if (Test-Path $audit) {
        php $audit
        if ($LASTEXITCODE -ne 0) {
            throw "Audit gagal."
        }
    }
    else {
        Write-Host "[WARN] cgone_audit_duplicate_migrations.php tidak ditemukan." -ForegroundColor Yellow
    }

    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "REPLACE MIGRATION SELESAI" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
}
catch {
    Write-Host ""
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
}
finally {
    try { Stop-Transcript | Out-Null } catch {}
    Pause-End
}
