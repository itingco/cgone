param(
    [string]$DemoDatabase = "cgone_demo2",
    [string]$Company = "PT CGOne Sample Indonesia",
    [string]$SeedDate = "2026-09-01",
    [string]$PgHost = "127.0.0.1",
    [int]$PgPort = 5432,
    [string]$PgAdminUser = "postgres"
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$logFile = Join-Path (Get-Location) "cgone_demo_seed_$stamp.txt"

function Step([string]$msg) {
    Write-Host ""
    Write-Host ">>> $msg" -ForegroundColor Cyan
}

function Ok([string]$msg) {
    Write-Host "[OK] $msg" -ForegroundColor Green
}

function Fail([string]$msg) {
    Write-Host "[FAIL] $msg" -ForegroundColor Red
    throw $msg
}

function Pause-End {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "SCRIPT SELESAI / BERHENTI" -ForegroundColor Yellow
    Write-Host "Log:" -ForegroundColor Yellow
    Write-Host $logFile -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Jika masih gagal, kirim file log ini ke ChatGPT." -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Tekan ENTER untuk menutup"
}

try {
    Start-Transcript -Path $logFile -Force | Out-Null

    Write-Host "CGOne Demo Company - Clean Recreate + Seed" -ForegroundColor White
    Write-Host "Started : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
    Write-Host "Folder  : $(Get-Location)" -ForegroundColor Gray
    Write-Host "Database: $DemoDatabase" -ForegroundColor Gray
    Write-Host "Company : $Company" -ForegroundColor Gray

    if (-not (Test-Path ".\artisan")) {
        Fail "Jalankan script dari root CGOne (folder yang memiliki artisan)."
    }

    if ($DemoDatabase -notmatch '^[A-Za-z_][A-Za-z0-9_]*$') {
        Fail "Nama database tidak valid: $DemoDatabase"
    }

    $erpMigration = ".\database\migrations\2026_08_30_000200_expand_erp_master_fields.php"
    $refMigration = ".\database\migrations\2026_08_30_000200_expand_reference_master_fields.php"

    Step "1. Validasi migration sebelum recreate"

    foreach ($f in @($erpMigration, $refMigration)) {
        if (-not (Test-Path $f)) {
            Fail "Migration tidak ditemukan: $f"
        }
    }

    $erpOldLength = Select-String -Path $erpMigration -SimpleMatch "`$t->decimal('length'" -ErrorAction SilentlyContinue
    $erpOldWidth  = Select-String -Path $erpMigration -SimpleMatch "`$t->decimal('width'" -ErrorAction SilentlyContinue

    if ($erpOldLength -or $erpOldWidth) {
        Fail "ERP migration masih membuat length/width. Jalankan cgone_debug_duplicate_length_v2.ps1 dulu."
    }

    $refLength = Select-String -Path $refMigration -SimpleMatch "`$t->decimal('length'" -ErrorAction SilentlyContinue
    $refWidth  = Select-String -Path $refMigration -SimpleMatch "`$t->decimal('width'" -ErrorAction SilentlyContinue

    if (-not $refLength -or -not $refWidth) {
        Fail "Reference migration tidak memiliki length/width."
    }

    Ok "Ownership length/width sudah benar"

    Write-Host "ERP SHA256 : $((Get-FileHash $erpMigration -Algorithm SHA256).Hash)"
    Write-Host "REF SHA256 : $((Get-FileHash $refMigration -Algorithm SHA256).Hash)"

    Step "2. PHP lint"
    php -l $erpMigration
    if ($LASTEXITCODE -ne 0) { Fail "PHP lint ERP migration gagal." }

    php -l $refMigration
    if ($LASTEXITCODE -ne 0) { Fail "PHP lint reference migration gagal." }

    Ok "PHP lint PASS"

    Step "3. Clear Laravel cache"
    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) {
        Fail "php artisan optimize:clear gagal."
    }
    Ok "Laravel cache clear"

    Step "4. Cari psql.exe"

    $psql = $null
    $cmd = Get-Command psql.exe -ErrorAction SilentlyContinue
    if ($cmd) {
        $psql = $cmd.Source
    }

    if (-not $psql) {
        $candidates = Get-ChildItem "C:\Program Files\PostgreSQL\*\bin\psql.exe" -ErrorAction SilentlyContinue |
            Sort-Object FullName -Descending

        if ($candidates) {
            $psql = $candidates[0].FullName
        }
    }

    if (-not $psql) {
        Fail "psql.exe tidak ditemukan. Kirim log ini; database bisa di-drop lewat DBeaver sebagai alternatif."
    }

    Ok "psql ditemukan: $psql"

    $oldPgPassword = $env:PGPASSWORD
    $passwordWasSet = -not [string]::IsNullOrWhiteSpace($oldPgPassword)

    try {
        if (-not $passwordWasSet) {
            $securePassword = Read-Host "Password PostgreSQL user [$PgAdminUser]" -AsSecureString
            $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)

            try {
                $env:PGPASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
            }
            finally {
                [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
            }
        }

        Step "5. Terminate semua koneksi ke [$DemoDatabase]"

        $terminateSql = @"
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = '$DemoDatabase'
  AND pid <> pg_backend_pid();
"@

        & $psql `
            -h $PgHost `
            -p $PgPort `
            -U $PgAdminUser `
            -d postgres `
            -v ON_ERROR_STOP=1 `
            -c $terminateSql

        if ($LASTEXITCODE -ne 0) {
            Fail "Terminate connection gagal."
        }

        Step "6. Drop database lama [$DemoDatabase]"

        $dropSql = "DROP DATABASE IF EXISTS `"$DemoDatabase`";"

        & $psql `
            -h $PgHost `
            -p $PgPort `
            -U $PgAdminUser `
            -d postgres `
            -v ON_ERROR_STOP=1 `
            -c $dropSql

        if ($LASTEXITCODE -ne 0) {
            Fail "DROP DATABASE gagal."
        }

        Ok "Database lama sudah dihapus"

        Step "7. Pastikan database benar-benar sudah tidak ada"

        $checkSql = "SELECT datname FROM pg_database WHERE datname = '$DemoDatabase';"

        $dbCheck = & $psql `
            -h $PgHost `
            -p $PgPort `
            -U $PgAdminUser `
            -d postgres `
            -t `
            -A `
            -v ON_ERROR_STOP=1 `
            -c $checkSql

        if ($LASTEXITCODE -ne 0) {
            Fail "Pengecekan database gagal."
        }

        if (($dbCheck | Out-String).Trim() -eq $DemoDatabase) {
            Fail "Database $DemoDatabase masih ada setelah DROP."
        }

        Ok "$DemoDatabase benar-benar sudah tidak ada"

        Step "8. Create + initialize + seed sample company"

        php artisan erp:seed-sample-company `
            --create-database=$DemoDatabase `
            --label="$Company" `
            --company="$Company" `
            --date=$SeedDate

        if ($LASTEXITCODE -ne 0) {
            Fail "Sample company seed gagal. Lihat error lengkap di atas/log."
        }

        Ok "Sample company berhasil dibuat"

        Step "9. Cek database sudah ada"

        $finalCheck = & $psql `
            -h $PgHost `
            -p $PgPort `
            -U $PgAdminUser `
            -d postgres `
            -t `
            -A `
            -v ON_ERROR_STOP=1 `
            -c $checkSql

        if (($finalCheck | Out-String).Trim() -ne $DemoDatabase) {
            Fail "Seeder selesai tetapi database tidak ditemukan."
        }

        Ok "Database [$DemoDatabase] tersedia"

        Write-Host ""
        Write-Host "============================================================" -ForegroundColor Green
        Write-Host "DEMO COMPANY BERHASIL" -ForegroundColor Green
        Write-Host "Database : $DemoDatabase" -ForegroundColor Green
        Write-Host "Company  : $Company" -ForegroundColor Green
        Write-Host "============================================================" -ForegroundColor Green
    }
    finally {
        if ($passwordWasSet) {
            $env:PGPASSWORD = $oldPgPassword
        }
        else {
            Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
        }
    }
}
catch {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Red
    Write-Host "ERROR" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host "==========================================" -ForegroundColor Red
}
finally {
    try {
        Stop-Transcript | Out-Null
    }
    catch {}

    Pause-End
}
