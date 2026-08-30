<?php
namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartnerAddress;
use App\Models\Customer;
use App\Models\Vendor;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessPartnerAddressController extends Controller
{
    private function owner(string $type, int $id): array
    {
        return match ($type) {
            'customer' => [Customer::findOrFail($id), 'master.customers', 'master.customers.show'],
            'vendor' => [Vendor::findOrFail($id), 'master.vendors', 'master.vendors.show'],
            default => abort(404),
        };
    }

    private function authorizeEdit(string $menuCode): void
    {
        $allowed = app(MenuAuthorizationService::class)->allows(auth()->user(), $menuCode, 'edit');
        abort_unless($allowed, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'address_type' => ['required', Rule::in(['BILLING','SHIPPING','OFFICE','WAREHOUSE','TAX','OTHER'])],
            'label' => ['nullable','string','max:100'],
            'contact_person' => ['nullable','string','max:150'],
            'phone' => ['nullable','string','max:50'],
            'address' => ['required','string'],
            'city' => ['nullable','string','max:120'],
            'state' => ['nullable','string','max:120'],
            'postal_code' => ['nullable','string','max:30'],
            'country' => ['nullable','string','max:120'],
            'is_default' => ['required','boolean'],
            'is_active' => ['required','boolean'],
        ]);
    }

    public function store(Request $request, string $type, int $id, ActivityLogService $audit)
    {
        [$owner, $menuCode, $returnRoute] = $this->owner($type, $id);
        $this->authorizeEdit($menuCode);
        $data = $this->validated($request);

        $address = DB::transaction(function () use ($owner, $data, $audit, $menuCode) {
            if ($data['is_default']) {
                $owner->addresses()->where('address_type', $data['address_type'])->update(['is_default' => false]);
            }
            $address = $owner->addresses()->create($data);
            $audit->record($menuCode, 'address_create', $owner, [], $address->toArray(), ['address_id' => $address->id]);
            return $address;
        });

        return redirect()->route($returnRoute, $owner)->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, int $id, BusinessPartnerAddress $address, ActivityLogService $audit)
    {
        [$owner, $menuCode, $returnRoute] = $this->owner($type, $id);
        $this->authorizeEdit($menuCode);
        abort_unless($address->addressable_type === $owner->getMorphClass() && (int)$address->addressable_id === (int)$owner->getKey(), 404);
        $data = $this->validated($request);
        $before = $address->toArray();

        DB::transaction(function () use ($owner, $address, $data, $audit, $menuCode, $before) {
            if ($data['is_default']) {
                $owner->addresses()->where('address_type', $data['address_type'])->where('id','<>',$address->id)->update(['is_default' => false]);
            }
            $address->update($data);
            $audit->record($menuCode, 'address_update', $owner, $before, $address->fresh()->toArray(), ['address_id' => $address->id]);
        });

        return redirect()->route($returnRoute, $owner)->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(string $type, int $id, BusinessPartnerAddress $address, ActivityLogService $audit)
    {
        [$owner, $menuCode, $returnRoute] = $this->owner($type, $id);
        $this->authorizeEdit($menuCode);
        abort_unless($address->addressable_type === $owner->getMorphClass() && (int)$address->addressable_id === (int)$owner->getKey(), 404);
        $before = $address->toArray();
        $address->delete();
        $audit->record($menuCode, 'address_delete', $owner, $before, [], ['address_id' => $address->id]);
        return redirect()->route($returnRoute, $owner)->with('success', 'Alamat berhasil dihapus.');
    }
}
