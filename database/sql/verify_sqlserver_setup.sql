/* Verify immutable-protection triggers after deployment */
SELECT
    t.name AS TriggerName,
    OBJECT_NAME(t.parent_id) AS TableName,
    t.is_disabled AS IsDisabled
FROM sys.triggers t
WHERE t.name IN (
    'TRG_item_ledgers_immutable',
    'TRG_customer_ledgers_immutable',
    'TRG_vendor_ledgers_immutable',
    'TRG_gl_entries_immutable',
    'TRG_gl_batches_immutable',
    'TRG_activity_logs_immutable'
)
ORDER BY TableName;
GO
