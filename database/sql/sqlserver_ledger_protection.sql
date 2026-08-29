/* INGCO ERP - SQL Server immutable ledger protection
   Run after php artisan migrate --seed.
   These triggers intentionally reject ALL UPDATE/DELETE operations on posted ledger tables.
*/
SET XACT_ABORT ON;
GO
CREATE OR ALTER TRIGGER dbo.TRG_item_ledgers_immutable ON dbo.item_ledgers INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Posted item ledger rows are immutable. Use adjustment/reversal.', 1; END;
GO
CREATE OR ALTER TRIGGER dbo.TRG_customer_ledgers_immutable ON dbo.customer_ledgers INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Posted customer ledger rows are immutable. Use adjustment/reversal.', 1; END;
GO
CREATE OR ALTER TRIGGER dbo.TRG_vendor_ledgers_immutable ON dbo.vendor_ledgers INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Posted vendor ledger rows are immutable. Use adjustment/reversal.', 1; END;
GO
CREATE OR ALTER TRIGGER dbo.TRG_gl_entries_immutable ON dbo.gl_entries INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Posted GL entry rows are immutable. Use adjustment/reversal.', 1; END;
GO
CREATE OR ALTER TRIGGER dbo.TRG_gl_batches_immutable ON dbo.gl_batches INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Posted GL batches are immutable. Use adjustment/reversal.', 1; END;
GO

CREATE OR ALTER TRIGGER dbo.TRG_activity_logs_immutable ON dbo.activity_logs INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51000, 'Activity logs are immutable.', 1; END;
GO
