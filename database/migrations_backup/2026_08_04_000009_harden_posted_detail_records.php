<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION erp_prevent_final_child_mutation()
RETURNS trigger AS $$
DECLARE
    parent_id bigint;
    parent_status text;
BEGIN
    parent_id := COALESCE(
        NULLIF(to_jsonb(OLD)->>TG_ARGV[1], '')::bigint,
        NULLIF(to_jsonb(NEW)->>TG_ARGV[1], '')::bigint
    );
    EXECUTE format('SELECT status FROM %I WHERE id = $1', TG_ARGV[0]) INTO parent_status USING parent_id;
    IF parent_status IN ('posted', 'reversed') THEN
        RAISE EXCEPTION 'Detail dokumen posted tidak dapat diubah. Gunakan adjustment atau reversal.'
            USING ERRCODE = 'integrity_constraint_violation';
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION erp_prevent_ledger_mutation()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'Ledger bersifat append-only. Gunakan adjustment atau reversal.'
        USING ERRCODE = 'integrity_constraint_violation';
END;
$$ LANGUAGE plpgsql;
SQL);

        $children = [
            'goods_receipt_lines' => ['goods_receipts', 'goods_receipt_id'],
            'supplier_invoice_lines' => ['supplier_invoices', 'supplier_invoice_id'],
            'journal_lines' => ['journal_entries', 'journal_entry_id'],
            'integration_lines' => ['integration_documents', 'integration_document_id'],
            'journal_adjustment_lines' => ['journal_adjustments', 'journal_adjustment_id'],
            'inventory_adjustment_lines' => ['inventory_adjustments', 'inventory_adjustment_id'],
        ];
        foreach ($children as $table => [$parent, $foreignKey]) {
            DB::statement("CREATE TRIGGER {$table}_final_parent_guard BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION erp_prevent_final_child_mutation('{$parent}', '{$foreignKey}')");
        }

        DB::statement('CREATE TRIGGER inventory_ledger_append_only BEFORE UPDATE OR DELETE ON inventory_ledger FOR EACH ROW EXECUTE FUNCTION erp_prevent_ledger_mutation()');
    }

    public function down(): void
    {
        foreach (['goods_receipt_lines','supplier_invoice_lines','journal_lines','integration_lines','journal_adjustment_lines','inventory_adjustment_lines'] as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_final_parent_guard ON {$table}");
        }
        DB::statement('DROP TRIGGER IF EXISTS inventory_ledger_append_only ON inventory_ledger');
        DB::statement('DROP FUNCTION IF EXISTS erp_prevent_ledger_mutation()');
        DB::statement('DROP FUNCTION IF EXISTS erp_prevent_final_child_mutation()');
    }
};
