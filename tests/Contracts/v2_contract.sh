#!/usr/bin/env bash
set -euo pipefail
fail=0
check_file(){ [[ -f "$1" ]] || { echo "MISSING $1"; fail=1; }; }
check_grep(){ local p="$1" f="$2"; grep -qE "$p" "$f" 2>/dev/null || { echo "MISSING PATTERN [$p] in $f"; fail=1; }; }

check_file database/migrations/2026_08_18_000100_create_posting_setup_tables.php
check_file database/migrations/2026_08_18_000200_create_operational_documents.php
check_file database/migrations/2026_08_18_000300_create_posted_documents.php
check_file app/Services/Documents/DocumentStateService.php
check_file app/Services/Posting/PostingAccountResolver.php
check_file app/Services/Posting/ShipmentPostingService.php
check_file app/Services/Posting/ReceiptPostingService.php
check_file app/Services/Posting/SalesInvoicePostingService.php
check_file app/Services/Posting/PurchaseInvoicePostingService.php
check_file app/Services/Posting/PostedDocumentUndoService.php
check_file database/sql/postgresql_posted_document_protection.sql
check_file resources/views/sales/documents/index.blade.php
check_file resources/views/purchase/documents/index.blade.php
check_file resources/views/posted/show.blade.php
check_file resources/views/configuration/posting-setup/index.blade.php

check_grep 'sales-requests' routes/web.php
check_grep 'posted-sales-invoices' routes/web.php
check_grep 'purchase-requests' routes/web.php
check_grep 'posted-purchase-invoices' routes/web.php
check_grep 'posting-setup' routes/web.php
check_grep 'sidebar-search' resources/views/layouts/app.blade.php
check_grep 'show-more' resources/views/layouts/app.blade.php
check_grep 'password-toggle' resources/views/auth/login.blade.php
check_grep "'release'" database/seeders/SecuritySeeder.php
check_grep "'undo'" database/seeders/SecuritySeeder.php

if [[ $fail -ne 0 ]]; then exit 1; fi
echo 'V2 contract OK'
