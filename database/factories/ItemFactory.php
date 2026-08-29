<?php
namespace Database\Factories; use App\Models\{Item,Uom}; use Illuminate\Database\Eloquent\Factories\Factory; class ItemFactory extends Factory { protected $model=Item::class; public function definition(): array{return ['code'=>strtoupper(fake()->unique()->bothify('ITEM-###??')),'name'=>fake()->words(3,true),'item_type'=>'INVENTORY','base_uom_id'=>Uom::factory(),'is_active'=>true];} }
