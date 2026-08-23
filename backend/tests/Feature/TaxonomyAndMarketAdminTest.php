<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyAndMarketAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_crud_on_taxonomy_markets_and_settings(): void
    {
        $this->artisan('system:init-foundation');

        $admin = User::where('email', 'admin@arikartech.com')->first();
        $this->actingAs($admin, 'sanctum');

        // 1. Categories CRUD
        $createCatRes = $this->postJson('/api/v1/admin/categories', [
            'name' => 'VR Headsets',
            'description' => 'Virtual reality headsets and accessories',
            'is_active' => true,
        ]);
        $createCatRes->assertStatus(201)
            ->assertJsonPath('data.name', 'VR Headsets');

        $catId = $createCatRes->json('data.id');

        $updateCatRes = $this->putJson("/api/v1/admin/categories/{$catId}", [
            'name' => 'VR & AR Headsets',
        ]);
        $updateCatRes->assertStatus(200)
            ->assertJsonPath('data.name', 'VR & AR Headsets');

        // 2. Brands CRUD
        $createBrandRes = $this->postJson('/api/v1/admin/brands', [
            'name' => 'Meta',
            'website' => 'https://meta.com',
            'is_active' => true,
        ]);
        $createBrandRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Meta');

        $brandId = $createBrandRes->json('data.id');

        $updateBrandRes = $this->putJson("/api/v1/admin/brands/{$brandId}", [
            'website' => 'https://about.meta.com',
        ]);
        $updateBrandRes->assertStatus(200);

        // 3. Markets Admin
        $market = Market::where('code', 'us')->first();
        $updateMarketRes = $this->putJson("/api/v1/admin/markets/{$market->id}", [
            'name' => 'United States of America',
            'is_active' => true,
        ]);
        $updateMarketRes->assertStatus(200)
            ->assertJsonPath('data.name', 'United States of America');

        // 4. Settings Admin
        $settingsRes = $this->postJson('/api/v1/admin/settings', [
            'settings' => [
                'site_name' => 'ARIKARTECH Platform',
                'contact_email' => 'support@arikartech.com',
            ],
        ]);
        $settingsRes->assertStatus(200)
            ->assertJsonPath('data.site_name', 'ARIKARTECH Platform');
    }
}
