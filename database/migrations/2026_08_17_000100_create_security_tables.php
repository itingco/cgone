<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
 Schema::create('roles', function(Blueprint $t){$t->id();$t->string('code',100)->unique();$t->string('name');$t->text('description')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
 Schema::create('menus', function(Blueprint $t){$t->id();$t->foreignId('parent_id')->nullable()->constrained('menus');$t->string('code',100)->unique();$t->string('label');$t->string('route_name')->nullable();$t->string('icon',100)->nullable();$t->integer('sort_order')->default(0);$t->boolean('is_active')->default(true);$t->timestamps();});
 Schema::create('permissions', function(Blueprint $t){$t->id();$t->string('code',100)->unique();$t->string('name');$t->timestamps();});
 Schema::create('user_roles', function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('role_id')->constrained()->cascadeOnDelete();$t->unique(['user_id','role_id']);});
 Schema::create('role_menu_permissions', function(Blueprint $t){$t->id();$t->foreignId('role_id')->constrained()->cascadeOnDelete();$t->foreignId('menu_id')->constrained()->cascadeOnDelete();$t->foreignId('permission_id')->constrained()->cascadeOnDelete();$t->unique(['role_id','menu_id','permission_id'],'rmp_unique');});
 } public function down(): void { Schema::dropIfExists('role_menu_permissions');Schema::dropIfExists('user_roles');Schema::dropIfExists('permissions');Schema::dropIfExists('menus');Schema::dropIfExists('roles'); } };
