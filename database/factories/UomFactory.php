<?php
namespace Database\Factories; use App\Models\Uom; use Illuminate\Database\Eloquent\Factories\Factory; class UomFactory extends Factory { protected $model=Uom::class; public function definition(): array{return ['code'=>strtoupper(fake()->unique()->lexify('U??')),'name'=>fake()->word(),'symbol'=>'U','is_active'=>true];} }
