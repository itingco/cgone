<?php
namespace Tests\Feature; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\Support\PermissionHelper; use Tests\TestCase;
class DashboardTest extends TestCase { use RefreshDatabase,PermissionHelper; public function test_authorized_user_can_view_dashboard(): void {$user=$this->userWithPermission('dashboard','view');$this->actingAs($user)->get('/dashboard')->assertOk();} }
