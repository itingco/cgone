param(
    [switch]$SkipAudit
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$logFile = Join-Path (Get-Location) "cgone_fix_all_duplicate_columns_$stamp.txt"

function Step([string]$Message) {
    Write-Host ""
    Write-Host ">>> $Message" -ForegroundColor Cyan
}

function Ok([string]$Message) {
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Fail([string]$Message) {
    Write-Host "[FAIL] $Message" -ForegroundColor Red
    throw $Message
}

function Pause-End {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "SCRIPT SELESAI / BERHENTI" -ForegroundColor Yellow
    Write-Host "Log:" -ForegroundColor Yellow
    Write-Host $logFile -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Jika ada FAIL, kirim file log ini ke ChatGPT." -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Tekan ENTER untuk menutup"
}

try {
    Start-Transcript -Path $logFile -Force | Out-Null

    Write-Host "CGOne - Fix ALL Duplicate Migration Columns" -ForegroundColor White
    Write-Host "Started : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
    Write-Host "Folder  : $(Get-Location)" -ForegroundColor Gray

    if (-not (Test-Path ".\artisan")) {
        Fail "Jalankan script dari root project CGOne."
    }

    $erp = ".\database\migrations\2026_08_30_000200_expand_erp_master_fields.php"
    $ref = ".\database\migrations\2026_08_30_000200_expand_reference_master_fields.php"

    foreach ($file in @($erp, $ref)) {
        if (-not (Test-Path $file)) {
            Fail "Migration tidak ditemukan: $file"
        }
    }

    $duplicates = @(
        @{ Table = "customers"; Column = "billing_postal_code" },
        @{ Table = "customers"; Column = "currency_code" },
        @{ Table = "customers"; Column = "fax" },
        @{ Table = "customers"; Column = "shipping_postal_code" },
        @{ Table = "customers"; Column = "website" },
        @{ Table = "vendors";   Column = "currency_code" },
        @{ Table = "vendors";   Column = "fax" },
        @{ Table = "vendors";   Column = "website" }
    )

    Step "1. Backup dua migration"

    $backupDir = ".\migration_backup_all_duplicates_$stamp"
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Copy-Item $erp (Join-Path $backupDir (Split-Path $erp -Leaf)) -Force
    Copy-Item $ref (Join-Path $backupDir (Split-Path $ref -Leaf)) -Force

    Ok "Backup: $backupDir"

    Step "2. Duplicate yang akan diperbaiki"
    foreach ($entry in $duplicates) {
        Write-Host ("  - {0}.{1}" -f $entry.Table, $entry.Column)
    }

    Step "3. Patch expand_erp_master_fields.php"

    $text = [System.IO.File]::ReadAllText((Resolve-Path $erp))

    foreach ($entry in $duplicates) {
        $column = [regex]::Escape($entry.Column)

        $blockPattern = "(?ms)^[ \t]*if\s*\(\s*in_array\(\s*'$column'\s*,\s*`$missing\s*,\s*true\s*\)\s*\)\s*\{\s*`$t->[^\;]+;\s*\}\s*\r?\n?"
        $legacyPattern = "(?m)^[ \t]*if\s*\(\s*!\s*Schema::hasColumn\(\s*'[^']+'\s*,\s*'$column'\s*\)\s*\)\s*`$t->[^\;]+;\s*\r?\n?"

        $before = $text
        $text = [regex]::Replace($text, $blockPattern, "")
        $text = [regex]::Replace($text, $legacyPattern, "")

        if ($before -eq $text) {
            Write-Host ("  {0}.{1}: block tidak ditemukan / sudah dihapus" -f $entry.Table, $entry.Column) -ForegroundColor DarkYellow
        } else {
            Write-Host ("  {0}.{1}: add-block dihapus" -f $entry.Table, $entry.Column) -ForegroundColor Green
        }
    }

    $downPos = $text.IndexOf("public function down")
    if ($downPos -lt 0) {
        Fail "public function down() tidak ditemukan."
    }

    $beforeDown = $text.Substring(0, $downPos)
    $downPart = $text.Substring($downPos)

    foreach ($entry in $duplicates) {
        $column = [regex]::Escape($entry.Column)
        $downPart = [regex]::Replace($downPart, "'$column'\s*,?", "")
    }

    $text = $beforeDown + $downPart

    $utf8 = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText((Resolve-Path $erp), $text, $utf8)

    Ok "ERP migration selesai dipatch"

    Step "4. Verifikasi ownership"

    $erpText = [System.IO.File]::ReadAllText((Resolve-Path $erp))
    $refText = [System.IO.File]::ReadAllText((Resolve-Path $ref))

    $failed = $false

    foreach ($entry in $duplicates) {
        $column = [regex]::Escape($entry.Column)
        $pattern = "\`$t->(?:string|text|longText|mediumText|integer|bigInteger|unsignedInteger|unsignedBigInteger|decimal|float|double|boolean|date|dateTime|timestamp|json|jsonb|uuid|char|foreignId|enum)\(\s*'$column'"

        $erpCount = ([regex]::Matches($erpText, $pattern)).Count
        $refCount = ([regex]::Matches($refText, $pattern)).Count

        Write-Host ("{0}.{1}: ERP={2} REF={3}" -f $entry.Table, $entry.Column, $erpCount, $refCount)

        if ($erpCount -ne 0 -or $refCount -lt 1) {
            $failed = $true
        }
    }

    if ($failed) {
        Fail "Verifikasi ownership duplicate column gagal."
    }

    Ok "Semua 8 field hanya dimiliki reference migration"

    Step "5. PHP lint"

    php -l $erp
    if ($LASTEXITCODE -ne 0) { Fail "PHP lint ERP migration gagal." }

    php -l $ref
    if ($LASTEXITCODE -ne 0) { Fail "PHP lint Reference migration gagal." }

    Ok "PHP lint PASS"

    Step "6. Clear Laravel cache"

    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { Fail "optimize:clear gagal." }

    Ok "Laravel cache clear"

    if (-not $SkipAudit) {
        Step "7. Jalankan audit duplicate migration bila tool tersedia"

        $auditTool = ".\cgone_audit_duplicate_migrations.php"

        if (Test-Path $auditTool) {
            php $auditTool
            if ($LASTEXITCODE -ne 0) {
                Fail "Audit duplicate migration gagal dijalankan."
            }
        } else {
            Write-Host "cgone_audit_duplicate_migrations.php tidak ditemukan; audit dilewati." -ForegroundColor Yellow
        }
    }

    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "PATCH DUPLICATE COLUMN SELESAI" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Jika audit menunjukkan 0 duplicate:" -ForegroundColor Yellow
    Write-Host "1. Drop cgone_demo2 yang gagal" -ForegroundColor Yellow
    Write-Host "2. Jalankan cgone_recreate_demo_company.ps1" -ForegroundColor Yellow
}
catch {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Red
    Write-Host "ERROR SCRIPT" -ForegroundColor Red
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
