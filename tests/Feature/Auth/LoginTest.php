<?php
namespace Tests\Feature\Auth; use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Hash; use Tests\TestCase;
class LoginTest extends TestCase { use RefreshDatabase; public function test_user_can_login_with_valid_credentials(): void {$user=User::factory()->create(['password'=>Hash::make('Secret123!')]);$this->post('/login',['email'=>$user->email,'password'=>'Secret123!','remember'=>0])->assertRedirect('/dashboard');$this->assertAuthenticatedAs($user);} }
