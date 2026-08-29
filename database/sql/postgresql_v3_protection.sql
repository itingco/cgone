-- CGOne ERP V3 - PostgreSQL protection for pricing and transfer workflow
-- Run after V3 migrations and the V2 immutable ledger protection script.

CREATE OR REPLACE FUNCTION cgone_reject_v3_immutable_change()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'CGOne ERP V3: % is immutable in its current state. Use application workflow / reversal.', TG_TABLE_NAME
        USING ERRCODE = '55000';
END;
$$;

-- Transfer receipts are posted evidence and are never edited/deleted.
DO $$
DECLARE tbl text;
BEGIN
    FOREACH tbl IN ARRAY ARRAY['goods_transfer_receipts','goods_transfer_receipt_lines'] LOOP
        IF to_regclass('public.' || tbl) IS NOT NULL THEN
            EXECUTE format('DROP TRIGGER IF EXISTS trg_cgone_v3_immutable_%I ON %I',tbl,tbl);
            EXECUTE format('CREATE TRIGGER trg_cgone_v3_immutable_%I BEFORE UPDATE OR DELETE ON %I FOR EACH ROW EXECUTE FUNCTION cgone_reject_v3_immutable_change()',tbl,tbl);
        END IF;
    END LOOP;
END $$;

-- Once a price batch is RELEASED its commercial values are frozen;
-- only approval decision fields may change after RELEASE while the batch is still RELEASED.
CREATE OR REPLACE FUNCTION cgone_guard_price_update_line()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE batch_status text;
BEGIN
    SELECT status INTO batch_status FROM price_update_batches WHERE id = OLD.price_update_batch_id;

    IF batch_status = 'OPEN' THEN
        RETURN CASE WHEN TG_OP='DELETE' THEN OLD ELSE NEW END;
    END IF;

    IF TG_OP='DELETE' THEN
        RAISE EXCEPTION 'CGOne ERP V3: price update lines cannot be deleted after RELEASE.' USING ERRCODE='55000';
    END IF;

    IF batch_status IN ('RELEASED','APPROVED','REJECTED','COMPLETED') THEN
        IF NEW.price_update_batch_id IS DISTINCT FROM OLD.price_update_batch_id
           OR NEW.item_id IS DISTINCT FROM OLD.item_id
           OR NEW.price_level_id IS DISTINCT FROM OLD.price_level_id
           OR NEW.uom_id IS DISTINCT FROM OLD.uom_id
           OR NEW.old_price IS DISTINCT FROM OLD.old_price
           OR NEW.new_price IS DISTINCT FROM OLD.new_price
           OR NEW.effective_date IS DISTINCT FROM OLD.effective_date
           OR NEW.validation_status IS DISTINCT FROM OLD.validation_status
           OR NEW.validation_message IS DISTINCT FROM OLD.validation_message
           OR NEW.notes IS DISTINCT FROM OLD.notes THEN
            RAISE EXCEPTION 'CGOne ERP V3: released price values are immutable.' USING ERRCODE='55000';
        END IF;

        IF batch_status <> 'RELEASED' THEN
            RAISE EXCEPTION 'CGOne ERP V3: finalized price update lines are immutable.' USING ERRCODE='55000';
        END IF;

        IF OLD.approval_status <> 'PENDING' THEN
            RAISE EXCEPTION 'CGOne ERP V3: price approval decision is already final.' USING ERRCODE='55000';
        END IF;
        IF NEW.approval_status NOT IN ('APPROVED','REJECTED') THEN
            RAISE EXCEPTION 'CGOne ERP V3: RELEASED line may only become APPROVED or REJECTED.' USING ERRCODE='55000';
        END IF;
        RETURN NEW;
    END IF;

    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_price_update_line ON price_update_lines;
CREATE TRIGGER trg_cgone_guard_price_update_line
BEFORE UPDATE OR DELETE ON price_update_lines
FOR EACH ROW EXECUTE FUNCTION cgone_guard_price_update_line();

-- APPROVED / REJECTED / COMPLETED batch headers are final audit records.
CREATE OR REPLACE FUNCTION cgone_guard_price_update_batch()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.status IN ('APPROVED','REJECTED','COMPLETED') THEN
        RAISE EXCEPTION 'CGOne ERP V3: finalized price update batch is immutable.' USING ERRCODE='55000';
    END IF;
    RETURN CASE WHEN TG_OP='DELETE' THEN OLD ELSE NEW END;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_price_update_batch ON price_update_batches;
CREATE TRIGGER trg_cgone_guard_price_update_batch
BEFORE UPDATE OR DELETE ON price_update_batches
FOR EACH ROW EXECUTE FUNCTION cgone_guard_price_update_batch();

-- Item price history cannot be deleted and its commercial identity/value cannot be rewritten.
-- The engine may only close an old period through effective_to / is_active.
CREATE OR REPLACE FUNCTION cgone_guard_item_price()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP='DELETE' THEN
        RAISE EXCEPTION 'CGOne ERP V3: item price history cannot be deleted.' USING ERRCODE='55000';
    END IF;
    IF NEW.item_id IS DISTINCT FROM OLD.item_id
       OR NEW.price_level_id IS DISTINCT FROM OLD.price_level_id
       OR NEW.uom_id IS DISTINCT FROM OLD.uom_id
       OR NEW.currency_code IS DISTINCT FROM OLD.currency_code
       OR NEW.price IS DISTINCT FROM OLD.price
       OR NEW.effective_from IS DISTINCT FROM OLD.effective_from
       OR NEW.price_update_batch_id IS DISTINCT FROM OLD.price_update_batch_id
       OR NEW.approved_by IS DISTINCT FROM OLD.approved_by
       OR NEW.approved_at IS DISTINCT FROM OLD.approved_at THEN
        RAISE EXCEPTION 'CGOne ERP V3: approved item price history is immutable; create a new effective-dated price.' USING ERRCODE='55000';
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_item_price ON item_prices;
CREATE TRIGGER trg_cgone_guard_item_price
BEFORE UPDATE OR DELETE ON item_prices
FOR EACH ROW EXECUTE FUNCTION cgone_guard_item_price();

-- Approved/rejected transfer requests and their lines are frozen.
CREATE OR REPLACE FUNCTION cgone_guard_transfer_request()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.status IN ('APPROVED','REJECTED') THEN
        RAISE EXCEPTION 'CGOne ERP V3: finalized Goods Transfer Request is immutable.' USING ERRCODE='55000';
    END IF;
    RETURN CASE WHEN TG_OP='DELETE' THEN OLD ELSE NEW END;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_transfer_request ON goods_transfer_requests;
CREATE TRIGGER trg_cgone_guard_transfer_request
BEFORE UPDATE OR DELETE ON goods_transfer_requests
FOR EACH ROW EXECUTE FUNCTION cgone_guard_transfer_request();

CREATE OR REPLACE FUNCTION cgone_guard_transfer_request_line()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE request_status text;
BEGIN
    SELECT status INTO request_status FROM goods_transfer_requests WHERE id=OLD.goods_transfer_request_id;
    IF request_status <> 'OPEN' THEN
        RAISE EXCEPTION 'CGOne ERP V3: Goods Transfer Request lines are frozen after release.' USING ERRCODE='55000';
    END IF;
    RETURN CASE WHEN TG_OP='DELETE' THEN OLD ELSE NEW END;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_transfer_request_line ON goods_transfer_request_lines;
CREATE TRIGGER trg_cgone_guard_transfer_request_line
BEFORE UPDATE OR DELETE ON goods_transfer_request_lines
FOR EACH ROW EXECUTE FUNCTION cgone_guard_transfer_request_line();

-- Transfer lines are frozen after shipment. Header status is allowed to progress SHIPPED -> RECEIVED/UNDO.
CREATE OR REPLACE FUNCTION cgone_guard_transfer_line()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE transfer_status text;
BEGIN
    SELECT status INTO transfer_status FROM goods_transfers WHERE id=OLD.goods_transfer_id;
    IF TG_OP='DELETE' AND transfer_status IN ('SHIPPED','RECEIVED','UNDO') THEN
        RAISE EXCEPTION 'CGOne ERP V3: shipped Goods Transfer lines cannot be deleted.' USING ERRCODE='55000';
    END IF;
    IF TG_OP='UPDATE' AND transfer_status IN ('RECEIVED','UNDO') THEN
        RAISE EXCEPTION 'CGOne ERP V3: completed Goods Transfer lines are immutable.' USING ERRCODE='55000';
    END IF;
    IF TG_OP='UPDATE' AND transfer_status='SHIPPED' THEN
        IF NEW.goods_transfer_id IS DISTINCT FROM OLD.goods_transfer_id
           OR NEW.goods_transfer_request_line_id IS DISTINCT FROM OLD.goods_transfer_request_line_id
           OR NEW.item_id IS DISTINCT FROM OLD.item_id
           OR NEW.quantity IS DISTINCT FROM OLD.quantity
           OR NEW.shipped_quantity IS DISTINCT FROM OLD.shipped_quantity
           OR NEW.unit_cost IS DISTINCT FROM OLD.unit_cost THEN
            RAISE EXCEPTION 'CGOne ERP V3: only received quantity may progress after shipment.' USING ERRCODE='55000';
        END IF;
    END IF;
    RETURN CASE WHEN TG_OP='DELETE' THEN OLD ELSE NEW END;
END;
$$;
DROP TRIGGER IF EXISTS trg_cgone_guard_transfer_line ON goods_transfer_lines;
CREATE TRIGGER trg_cgone_guard_transfer_line
BEFORE UPDATE OR DELETE ON goods_transfer_lines
FOR EACH ROW EXECUTE FUNCTION cgone_guard_transfer_line();
