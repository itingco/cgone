<?php
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_partner_addresses', function (Blueprint $t) {
            $t->id();
            $t->morphs('addressable');
            $t->string('address_type', 30)->default('OTHER');
            $t->string('label', 100)->nullable();
            $t->string('contact_person', 150)->nullable();
            $t->string('phone', 50)->nullable();
            $t->text('address');
            $t->string('city', 120)->nullable();
            $t->string('state', 120)->nullable();
            $t->string('postal_code', 30)->nullable();
            $t->string('country', 120)->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['addressable_type', 'addressable_id', 'address_type', 'is_default'], 'bp_address_lookup_idx');
        });

        $now = now();
        if (Schema::hasTable('customers')) {
            foreach (DB::table('customers')->orderBy('id')->get() as $customer) {
                if (!empty($customer->address)) {
                    DB::table('business_partner_addresses')->insert([
                        'addressable_type' => Customer::class,
                        'addressable_id' => $customer->id,
                        'address_type' => 'BILLING',
                        'label' => 'Billing Address',
                        'phone' => $customer->phone ?? null,
                        'address' => $customer->address,
                        'city' => $customer->billing_city ?? null,
                        'state' => $customer->billing_state ?? null,
                        'country' => $customer->billing_country ?? null,
                        'is_default' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if (!empty($customer->shipping_address)) {
                    DB::table('business_partner_addresses')->insert([
                        'addressable_type' => Customer::class,
                        'addressable_id' => $customer->id,
                        'address_type' => 'SHIPPING',
                        'label' => 'Shipping Address',
                        'phone' => $customer->phone ?? null,
                        'address' => $customer->shipping_address,
                        'city' => $customer->shipping_city ?? null,
                        'state' => $customer->shipping_state ?? null,
                        'country' => $customer->shipping_country ?? null,
                        'is_default' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasTable('vendors')) {
            foreach (DB::table('vendors')->orderBy('id')->get() as $vendor) {
                if (!empty($vendor->address)) {
                    DB::table('business_partner_addresses')->insert([
                        'addressable_type' => Vendor::class,
                        'addressable_id' => $vendor->id,
                        'address_type' => 'OFFICE',
                        'label' => 'Main Office',
                        'phone' => $vendor->phone ?? null,
                        'address' => $vendor->address,
                        'city' => $vendor->city ?? null,
                        'state' => $vendor->state ?? null,
                        'country' => $vendor->country ?? null,
                        'is_default' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_partner_addresses');
    }
};
