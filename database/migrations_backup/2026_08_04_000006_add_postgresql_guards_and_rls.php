<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION erp_prevent_posted_mutation()
RETURNS trigger AS $$
BEGIN
    IF OLD.status IN ('posted', 'reversed') THEN
        RAISE EXCEPTION 'Dokumen % sudah %, gunakan adjustment atau reversal.', OLD.id, OLD.status
            USING ERRCODE = 'integrity_constraint_violation';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION erp_current_company_id()
RETURNS bigint AS $$
BEGIN
    RETURN NULLIF(current_setting('app.company_id', true), '')::bigint;
EXCEPTION WHEN others THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql STABLE;
SQL);

        $immutableTables = [
            'purchase_requests', 'purchase_orders', 'supplier_advances',
            'goods_receipts', 'supplier_invoices', 'supplier_payments',
            'journal_entries', 'inventory_transactions',
        ];

        foreach ($immutableTables as $table) {
            DB::statement("CREATE TRIGGER {$table}_immutable_posted BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION erp_prevent_posted_mutation()", []);
        }

        $tenantTables = [
            'branches','warehouses','document_sequences',
            'price_levels','customers','suppliers','item_costing_policies',
            'price_change_batches','price_change_lines','item_prices','approval_requests',
            'purchase_requests','purchase_orders','supplier_advances','goods_receipts',
            'supplier_invoices','supplier_payments','fiscal_periods','accounts','posting_profiles',
            'journal_entries','inventory_transactions','inventory_ledger','budgets',
            'integration_sources','integration_documents','integration_exceptions','report_definitions','audit_logs',
        ];

        foreach ($tenantTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY {$table}_company_policy ON {$table} USING (company_id IS NULL OR company_id = erp_current_company_id()) WITH CHECK (company_id IS NULL OR company_id = erp_current_company_id())");
        }

        foreach (['uoms', 'item_categories'] as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY {$table}_group_policy ON {$table} USING (group_id = (SELECT group_id FROM companies WHERE id = erp_current_company_id())) WITH CHECK (group_id = (SELECT group_id FROM companies WHERE id = erp_current_company_id()))");
        }

        DB::statement('ALTER TABLE items ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE items FORCE ROW LEVEL SECURITY');
        DB::statement("CREATE POLICY items_group_company_policy ON items USING (group_id = (SELECT group_id FROM companies WHERE id = erp_current_company_id()) AND (company_id IS NULL OR company_id = erp_current_company_id())) WITH CHECK (group_id = (SELECT group_id FROM companies WHERE id = erp_current_company_id()) AND (company_id IS NULL OR company_id = erp_current_company_id()))");

        $childPolicies = [
            'item_variants' => ['items', 'item_id'],
            'item_uoms' => ['items', 'item_id'],
            'item_barcodes' => ['items', 'item_id'],
            'approval_actions' => ['approval_requests', 'approval_request_id'],
            'purchase_request_lines' => ['purchase_requests', 'purchase_request_id'],
            'purchase_order_lines' => ['purchase_orders', 'purchase_order_id'],
            'goods_receipt_lines' => ['goods_receipts', 'goods_receipt_id'],
            'supplier_invoice_lines' => ['supplier_invoices', 'supplier_invoice_id'],
            'supplier_advance_allocations' => ['supplier_advances', 'supplier_advance_id'],
            'journal_lines' => ['journal_entries', 'journal_entry_id'],
            'integration_lines' => ['integration_documents', 'integration_document_id'],
        ];
        foreach ($childPolicies as $table => [$parent, $foreignKey]) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            $parentCompanyExpression = $parent === 'items'
                ? "EXISTS (SELECT 1 FROM items p WHERE p.id = {$table}.{$foreignKey} AND p.group_id = (SELECT group_id FROM companies WHERE id = erp_current_company_id()) AND (p.company_id IS NULL OR p.company_id = erp_current_company_id()))"
                : "EXISTS (SELECT 1 FROM {$parent} p WHERE p.id = {$table}.{$foreignKey} AND p.company_id = erp_current_company_id())";
            DB::statement("CREATE POLICY {$table}_parent_policy ON {$table} USING ({$parentCompanyExpression}) WITH CHECK ({$parentCompanyExpression})");
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION erp_prevent_audit_mutation()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'Audit log bersifat append-only.' USING ERRCODE = 'integrity_constraint_violation';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER audit_logs_append_only
BEFORE UPDATE OR DELETE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION erp_prevent_audit_mutation();

CREATE OR REPLACE FUNCTION erp_guard_price_change_line()
RETURNS trigger AS $$
BEGIN
    IF OLD.status IN ('active', 'rejected') THEN
        RAISE EXCEPTION 'Perubahan harga final tidak dapat diedit. Buat pengajuan baru.'
            USING ERRCODE = 'integrity_constraint_violation';
    END IF;

    IF OLD.status = 'scheduled' THEN
        IF TG_OP = 'DELETE'
           OR NEW.status <> 'active'
           OR NEW.item_id <> OLD.item_id
           OR NEW.uom_id <> OLD.uom_id
           OR NEW.price_level_id <> OLD.price_level_id
           OR NEW.new_price <> OLD.new_price
           OR NEW.effective_at <> OLD.effective_at THEN
            RAISE EXCEPTION 'Harga terjadwal hanya dapat diaktifkan oleh scheduler.'
                USING ERRCODE = 'integrity_constraint_violation';
        END IF;
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER price_change_lines_final_guard
BEFORE UPDATE OR DELETE ON price_change_lines
FOR EACH ROW EXECUTE FUNCTION erp_guard_price_change_line();
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS price_change_lines_final_guard ON price_change_lines');
        DB::statement('DROP FUNCTION IF EXISTS erp_guard_price_change_line()');
        DB::statement('DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs');
        DB::statement('DROP FUNCTION IF EXISTS erp_prevent_audit_mutation()');

        foreach (['purchase_requests','purchase_orders','supplier_advances','goods_receipts','supplier_invoices','supplier_payments','journal_entries','inventory_transactions'] as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_immutable_posted ON {$table}");
        }

        DB::statement('DROP FUNCTION IF EXISTS erp_prevent_posted_mutation()');
        DB::statement('DROP FUNCTION IF EXISTS erp_current_company_id()');
    }
};
