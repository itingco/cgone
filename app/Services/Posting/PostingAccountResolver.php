<?php
namespace App\Services\Posting;

use App\Models\{Customer,Item,PostingSetup,Vendor};
use DomainException;

class PostingAccountResolver
{
    public function inventory(Item $item): int { return (int)($item->inventoryPostingGroup?->inventory_account_id ?: $item->inventory_account_id ?: throw new DomainException("Inventory account is not configured for item {$item->code}.")); }
    public function cogs(Item $item): int { return (int)($item->inventoryPostingGroup?->cogs_account_id ?: $item->cogs_account_id ?: throw new DomainException("COGS account is not configured for item {$item->code}.")); }
    public function sales(Item $item): int { return (int)($item->generalProductPostingGroup?->sales_account_id ?: $item->sales_account_id ?: throw new DomainException("Sales account is not configured for item {$item->code}.")); }
    public function purchase(Item $item): int { return (int)($item->generalProductPostingGroup?->purchase_account_id ?: throw new DomainException("Purchase account is not configured for item {$item->code}.")); }
    public function receivable(Customer $customer): int { return (int)($customer->customerPostingGroup?->receivable_account_id ?: $customer->receivable_account_id ?: throw new DomainException("AR account is not configured for customer {$customer->code}.")); }
    public function payable(Vendor $vendor): int { return (int)($vendor->vendorPostingGroup?->payable_account_id ?: $vendor->payable_account_id ?: throw new DomainException("AP account is not configured for vendor {$vendor->code}.")); }
    public function outputTax(Item $item, Customer $customer): ?int { return $item->taxPostingGroup?->output_tax_account_id ?: $customer->taxPostingGroup?->output_tax_account_id; }
    public function inputTax(Item $item, Vendor $vendor): ?int { return $item->taxPostingGroup?->input_tax_account_id ?: $vendor->taxPostingGroup?->input_tax_account_id; }
    public function grni(): int { $row=PostingSetup::where('code','DEFAULT')->where('is_active',true)->first(); return (int)($row?->grni_account_id ?: throw new DomainException('GRNI Account is not configured in Posting Setup.')); }
}
