<?php

$salesFields = [
    'status' => [
        'label' => 'Document Status',
        'column' => 'status',
        'operators' => ['=', '!=', 'IN', 'NOT IN'],
    ],
    'customer_id' => [
        'label' => 'Customer ID',
        'column' => 'customer_id',
        'operators' => ['=', '!=', 'IN', 'NOT IN'],
    ],
    'warehouse_id' => [
        'label' => 'Warehouse ID',
        'column' => 'warehouse_id',
        'operators' => ['=', '!=', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'],
    ],
    'currency_code' => [
        'label' => 'Currency',
        'column' => 'currency_code',
        'operators' => ['=', '!=', 'IN', 'NOT IN'],
    ],
    'created_by' => [
        'label' => 'Created By User ID',
        'column' => 'created_by',
        'operators' => ['=', '!=', 'IN', 'NOT IN'],
    ],
];

return [
    // Only whitelisted physical fields may be used. Never accept arbitrary SQL column names.
    'sales.request' => $salesFields,
    'sales.order' => $salesFields,
    'sales.shipment' => $salesFields,
    'sales.invoice' => $salesFields,
];
