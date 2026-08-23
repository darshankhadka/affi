<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_unauthenticated_request_to_admin_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_super_admin_can_access_admin_dashboard_and_manage_users(): void
    {
        $admin = User::where('email', 'admin@arikartech.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $usersResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $usersResponse->assertStatus(200);
    }

    public function test_analyst_cannot_create_or_delete_products(): void
    {
        $analyst = User::create([
            'name' => 'Analyst User',
            'email' => 'analyst@arikartech.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
        $analyst->assignRole('Analyst');
        $token = $analyst->createToken('analyst_token')->plainTextToken;

        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
        $cat = Category::first();

        // Attempt creation (should fail 403)
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/products', [
                'brand_id' => $brand->id,
                'category_id' => $cat->id,
                'name' => 'Unauthorized Product',
                'status' => 'draft',
            ]);

        $createResponse->assertStatus(403);
    }
}
